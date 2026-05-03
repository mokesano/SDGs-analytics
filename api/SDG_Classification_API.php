<?php
/**
 * SDG Classification API
 * Sistem klasifikasi SDG dengan orientasi dampak yang lebih kuat
 *
 * Endpoint Baru (Anti-Timeout / Sequential):
 * - ?orcid=xxx&action=init          → Info peneliti + daftar karya (tanpa SDG)
 * - ?orcid=xxx&action=batch&offset=0&limit=3 → Analisis SDG per batch
 * - ?orcid=xxx&action=summary       → Agregasi semua batch → ringkasan peneliti
 *
 * Endpoint Lama (tetap didukung):
 * - ?orcid=xxx         → Full analisis (legacy, bisa timeout jika karya banyak)
 * - ?doi=xxx           → Analisis satu artikel
 *
 * @author Rochmady and Wizdam Team
 * @version 1.0.0
 * @license MIT
 */

// -----------------------------------------------------------------
// BAGIAN #1: MONITORING (UP/DOWN)
// -----------------------------------------------------------------
if (empty($_GET)) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'up', 'message' => 'Endpoint is operational', 'version' => 'v1.0.0']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// -----------------------------------------------------------------
// KONFIGURASI
// -----------------------------------------------------------------
define('BATCH_SIZE', 5); // Jumlah karya diproses per request batch

$CACHE_DIR = __DIR__ . '/cache';
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0755, true);
}

$CONFIG = [
    'MIN_SCORE_THRESHOLD'       => 0.20,
    'CONFIDENCE_THRESHOLD'      => 0.30,
    'HIGH_CONFIDENCE_THRESHOLD' => 0.60,
    'MAX_SDGS_PER_WORK'         => 7,
    'KEYWORD_WEIGHT'            => 0.30,
    'SIMILARITY_WEIGHT'         => 0.30,
    'SUBSTANTIVE_WEIGHT'        => 0.20,
    'CAUSAL_WEIGHT'             => 0.20,
    'ACTIVE_CONTRIBUTOR_THRESHOLD'   => 0.50,
    'RELEVANT_CONTRIBUTOR_THRESHOLD' => 0.35,
    'DISCUSSANT_THRESHOLD'           => 0.25,
    'CACHE_TTL'                 => 604800,
];

error_reporting(E_ALL & ~E_NOTICE);

if (!isset($MEMORY_CACHE))       $MEMORY_CACHE = [];
if (!isset($CAUSAL_PATTERNS))    $CAUSAL_PATTERNS = ['contributes to','supports','advances','helps achieve','improves','untuk','agar','supaya','mendukung','membantu'];
if (!isset($TRANSFORMATIVE_VERBS)) $TRANSFORMATIVE_VERBS = ['develop','implement','improve','enhance','establish','strengthen','mengembangkan','mengimplementasikan','meningkatkan','memperbaiki'];

// -----------------------------------------------------------------
// SDG KEYWORDS (tidak berubah dari v5.1.8)
// -----------------------------------------------------------------
$SDG_KEYWORDS = [
    "SDG1"  => ["poverty","inequality","social protection","economic disparity","vulnerable population","basic services","financial inclusion","social security","welfare","homelessness","slum","basic income","extreme poverty","social safety net","underprivileged","income inequality","marginalized communities","poverty eradication","poverty reduction","socioeconomic","disadvantaged","low-income","resource allocation","poverty line","inclusive growth","pro-poor","rural poverty","urban poverty","wealth distribution","social mobility","income distribution","microfinance","kemiskinan","ketimpangan","perlindungan sosial","kesenjangan ekonomi","populasi rentan","layanan dasar","inklusi keuangan","jaminan sosial","kesejahteraan","tunawisma","permukiman kumuh","pendapatan dasar","kemiskinan ekstrem","jaring pengaman sosial","masyarakat kurang mampu","pengentasan kemiskinan","pengurangan kemiskinan","pertumbuhan inklusif","pendapatan rendah","ketimpangan pendapatan","akses layanan dasar","mobilitas sosial","distribusi kekayaan","pembangunan pro-rakyat","pemberdayaan masyarakat miskin","pembiayaan mikro","komunitas terpinggirkan"],
    "SDG2"  => ["hunger","food security","agriculture","nutrition","sustainable farming","food system","malnutrition","crop","livestock","irrigation","food production","agricultural productivity","food access","food shortage","farming","food waste","food supply","food safety","rural development","food sovereignty","sustainable agriculture","agro-ecology","food price","food inflation","agricultural research","fisheries","aquaculture","agricultural innovation","food distribution","hunger eradication","famine","agricultural policy","kelaparan","ketahanan pangan","pertanian","nutrisi","pertanian berkelanjutan","sistem pangan","malnutrisi","tanaman","ternak","irigasi","produksi pangan","akses pangan","kekurangan pangan","limbah pangan","keamanan pangan","pengembangan pedesaan","kedaulatan pangan","harga pangan","perikanan","akuakultur","inovasi pertanian","distribusi pangan","penghapusan kelaparan","krisis pangan","kebijakan pertanian"],
    "SDG3"  => ["health","disease","vaccine","mental health","infectious disease","public health","child mortality","maternal health","hospital","clinical","HIV","malaria","tuberculosis","noncommunicable","sanitation","wellbeing","pandemic","epidemic","medical treatment","healthcare","doctor","nurse","surgery","injury","medication","immunization","nutrition","hospitalization","health policy","life expectancy","patient care","healthcare access","preventive medicine","medical research","wellness","kesehatan","penyakit","vaksin","kesehatan mental","penyakit menular","kesehatan masyarakat","kematian anak","kesehatan ibu","rumah sakit","klinis","imunisasi","pengobatan","perawatan","obat-obatan","dokter","perawat","sanitasi","gizi","akses layanan kesehatan","pengobatan preventif","harapan hidup","penelitian medis","pandemi","epidemi"],
    "SDG4"  => ["education","learning","school","teaching","literacy","higher education","academic","curriculum","classroom","student","educational policy","distance learning","e-learning","teacher training","vocational training","lifelong learning","primary education","secondary education","university","educational resources","scholarship","educational access","education quality","schooling","science education","pedagogy","educational inequality","educational technology","inclusive education","special education","early childhood education","STEM","pendidikan","pembelajaran","sekolah","pengajaran","literasi","pendidikan tinggi","akademik","kurikulum","ruang kelas","siswa","murid","pelajar","mahasiswa","kebijakan pendidikan","pembelajaran jarak jauh","pelatihan guru","pelatihan vokasi","belajar sepanjang hayat","pendidikan dasar","pendidikan menengah","akses pendidikan","kualitas pendidikan","kesetaraan pendidikan","teknologi pendidikan","pendidikan inklusif","pendidikan khusus","pendidikan anak usia dini"],
    "SDG5"  => ["gender equality","women empowerment","gender discrimination","gender-based violence","gender parity","equal rights","gender gap","female participation","gender mainstreaming","feminism","sexual harassment","gender stereotypes","gender bias","women's rights","women in leadership","women's health","gender perspective","gender analysis","gender inclusive","gender sensitive","maternal","women's education","women entrepreneurship","gender equity","women workforce","women representation","gender pay gap","sexual violence","women's economic empowerment","gender diversity","gender identity","women in stem","kesetaraan gender","pemberdayaan perempuan","diskriminasi gender","kekerasan berbasis gender","paritas gender","hak perempuan","kesetaraan hak","partisipasi perempuan","kepemimpinan perempuan","kesehatan perempuan","pendidikan perempuan","pengusaha perempuan","kesenjangan upah","kekerasan seksual","keragaman gender","perspektif gender","analisis gender","inklusif gender","sensitivitas gender","identitas gender"],
    "SDG6"  => ["clean water","sanitation","water quality","wastewater","water access","water shortage","water resource","water management","water pollution","drinking water","water supply","water scarcity","water utility","water treatment","water reuse","water conservation","handwashing","hygiene","water system","water infrastructure","water security","contaminated water","groundwater","watershed","water stress","water efficiency","water harvesting","water filtration","sustainable water","water monitoring","hydrological","water governance","water cycle","air bersih","sanitasi","kualitas air","air limbah","akses air","kelangkaan air","sumber daya air","pengelolaan air","pencemaran air","air minum","pasokan air","pengolahan air","konservasi air","cuci tangan","kebersihan","infrastruktur air","keamanan air","air tanah","tangkapan air","daur ulang air","efisiensi air"],
    "SDG7"  => ["renewable energy","clean energy","energy access","energy efficiency","sustainable energy","solar energy","wind energy","hydropower","geothermal","biomass energy","biofuel","energy storage","energy infrastructure","energy grid","energy security","electricity access","power generation","green energy","energy poverty","energy conservation","energy policy","energy transition","fossil fuel","carbon emission","energy consumption","energy production","alternative energy","fuel efficiency","energy innovation","energy resources","energy system","energy technology","net zero","energi terbarukan","energi bersih","akses energi","efisiensi energi","energi berkelanjutan","energi surya","energi angin","tenaga air","panas bumi","energi biomassa","biofuel","infrastruktur energi","jaringan listrik","keamanan energi","pembangkit listrik","kemiskinan energi","konservasi energi","transisi energi","energi alternatif"],
    "SDG8"  => ["economic growth","employment","decent work","job creation","labor market","productivity","entrepreneurship","sustainable tourism","financial services","labor rights","workforce","business development","small enterprises","medium enterprises","job security","labor policy","economic development","economic diversification","economic productivity","formal employment","informal employment","unemployment","underemployment","labor standards","economic opportunity","job training","job skills","economic resilience","economic inclusion","income growth","livelihood","worker protection","full employment","pertumbuhan ekonomi","lapangan kerja","pekerjaan layak","penciptaan lapangan kerja","pasar tenaga kerja","produktivitas","kewirausahaan","pariwisata berkelanjutan","layanan keuangan","hak tenaga kerja","pengembangan bisnis","usaha kecil","usaha menengah","keamanan kerja","pengangguran","setengah pengangguran","peluang ekonomi","pelatihan kerja","ketahanan ekonomi","inklusivitas ekonomi","pendapatan berkelanjutan"],
    "SDG9"  => ["infrastructure","innovation","industrialization","technology","research development","manufacturing","industrial diversification","technological capabilities","industrial policy","sustainable infrastructure","resilient infrastructure","industrial growth","industrial productivity","scientific research","information technology","communication technology","technological innovation","digital divide","digital access","digital inclusion","internet access","broadband","rural infrastructure","transportation infrastructure","clean technology","technology transfer","R&D investment","small-scale industry","medium-scale industry","engineering","technical capacity","digital infrastructure","industrial development","infrastruktur","inovasi","industrialisasi","teknologi","penelitian dan pengembangan","manufaktur","diversifikasi industri","kapasitas teknologi","kebijakan industri","infrastruktur berkelanjutan","infrastruktur tangguh","pertumbuhan industri","produktivitas industri","riset ilmiah","teknologi informasi","teknologi komunikasi","inovasi teknologi","akses digital","inklusivitas digital","akses internet"],
    "SDG10" => ["reduced inequalities","migration","income inequality","social inclusion","equality","equal opportunity","social protection","fiscal policy","discriminatory policies","social inequality","economic inequality","wage gap","social disparity","economic disparity","social exclusion","marginalized","social mobility","wealth distribution","income distribution","migrant rights","minority rights","racial equality","gender equality","social equity","economic empowerment","inclusive society","wage discrimination","social status","socioeconomic status","disadvantaged groups","affirmative action","economic opportunity","inequality reduction","ketimpangan berkurang","migrasi","ketimpangan pendapatan","inklusi sosial","kesetaraan","kesetaraan kesempatan","perlindungan sosial","kebijakan fiskal","kebijakan diskriminatif","kesenjangan sosial","pengucilan sosial","disparitas ekonomi","kelompok terpinggirkan","distribusi pendapatan","mobilitas sosial","hak minoritas","hak migran","kesetaraan ras","kebijakan afirmatif","pemberdayaan ekonomi","masyarakat inklusif"],
    "SDG11" => ["sustainable cities","urban planning","housing","transport","waste management","air quality","public spaces","urban development","slum upgrading","resilient buildings","disaster risk reduction","cultural heritage","city planning","urban infrastructure","sustainable transport","green spaces","urban resilience","urbanization","metropolitan planning","smart cities","inclusive cities","urban sustainability","urban policies","urban environment","urban health","urban biodiversity","urban sprawl","urban slums","urban governance","urban mobility","urban safety","urban agriculture","green building","kota berkelanjutan","permukiman layak","perencanaan kota","transportasi umum","perumahan terjangkau","urbanisasi","pemukiman kumuh","pembangunan perkotaan","infrastruktur kota","ruang publik","tata ruang kota","kepadatan penduduk","pembangunan wilayah","mobilitas perkotaan","resiliensi kota","pengurangan risiko bencana","kota pintar","akses transportasi","pengelolaan kota","lingkungan urban"],
    "SDG12" => ["responsible consumption","waste management","sustainable consumption","sustainable production","resource efficiency","natural resources","material footprint","ecological footprint","recycling","reuse","lifecycle management","sustainable procurement","eco-labeling","sustainable practices","corporate sustainability","circular economy","sustainable lifestyle","waste reduction","food waste","sustainable supply chain","industrial ecology","green products","chemical management","electronic waste","plastic waste","biodegradable","environmental impact","consumption patterns","waste disposal","sustainable materials","resource management","zero waste","waste-to-energy","konsumsi berkelanjutan","produksi berkelanjutan","limbah","daur ulang","efisiensi sumber daya","polusi","jejak karbon","rantai pasok","ekonomi sirkular","bahan kimia berbahaya","manajemen limbah","sampah makanan","energi efisien","penggunaan sumber daya","produk ramah lingkungan","pengurangan limbah","kesadaran konsumen","keberlanjutan industri","label ramah lingkungan","produksi hijau"],
    "SDG13" => ["climate change","global warming","greenhouse gas","carbon emission","carbon footprint","climate action","climate policy","climate mitigation","climate adaptation","emission reduction","climate resilience","carbon neutral","carbon sequestration","climate finance","climate technology","climate science","climate impact","extreme weather","climate vulnerability","carbon pricing","low carbon","carbon dioxide","methane emission","fossil fuel","renewable energy","climate justice","climate agreement","climate risk","climate education","climate model","decarbonization","climate emergency","climate crisis","perubahan iklim","pemanasan global","adaptasi iklim","mitigasi iklim","gas rumah kaca","emisi karbon","energi bersih","risiko iklim","bencana iklim","strategi iklim","kebijakan iklim","kerentanan iklim","cuaca ekstrem","pengurangan emisi","netral karbon","ketahanan iklim","penghitungan karbon","transisi hijau","aksi iklim","perjanjian Paris"],
    "SDG14" => ["life below water","marine pollution","ocean acidification","coastal ecosystem","marine resources","sustainable fishing","overfishing","marine conservation","marine protected areas","marine biodiversity","ocean health","marine litter","marine habitat","coral reef","marine species","ocean governance","blue economy","coastal management","marine science","fishing practices","fishing communities","aquatic ecosystem","seafood","maritime","underwater life","ocean sustainability","sea level rise","marine environment","ocean policy","fisheries management","ocean temperature","marine ecology","marine sanctuaries","lautan","ekosistem laut","perikanan berkelanjutan","pencemaran laut","keanekaragaman hayati laut","pengasaman laut","zona pesisir","konservasi laut","perlindungan laut","terumbu karang","biota laut","plastik di laut","pengelolaan laut","sumber daya kelautan","ekonomi biru","penangkapan ikan berlebihan","restorasi laut","sampah laut","marine protected area","ekosistem pesisir"],
    "SDG15" => ["life on land","biodiversity","deforestation","ecosystem","forest management","land degradation","desertification","wildlife conservation","protected species","protected areas","habitat conservation","land use","soil erosion","soil health","invasive species","natural habitat","afforestation","reforestation","sustainable forestry","biodiversity loss","endangered species","terrestrial ecosystem","mountain ecosystem","land restoration","conservation efforts","poaching","flora","fauna","wetlands","grasslands","biomass","land rights","seed diversity","genetic diversity","keanekaragaman hayati","konservasi hutan","penggundulan hutan","restorasi lahan","kerusakan lahan","penggurunan","keanekaragaman genetik","ekosistem darat","pertanian berkelanjutan","kehutanan","pengelolaan hutan","reboisasi","deforestasi","flora dan fauna","spesies langka","pelestarian alam","konservasi satwa liar","kawasan lindung","tanah dan air","ekologi"],
    "SDG16" => ["peace","justice","strong institutions","violence reduction","governance","rule of law","accountability","transparency","corruption","bribery","institutional capacity","decision-making","fundamental freedoms","legal identity","human rights","conflict resolution","peacebuilding","democracy","inclusive society","public access","judicial system","responsive institutions","violence against children","trafficking","arms flow","organized crime","national security","public policy","law enforcement","civil justice","fair trial","political participation","international cooperation","perdamaian","keadilan","hak asasi manusia","hukum","anti korupsi","keamanan publik","kekerasan","perlindungan hukum","akses keadilan","transparansi","akuntabilitas","pembangunan institusi","lembaga pemerintahan","konflik sosial","mediasi","hak warga negara","partisipasi publik","penegakan hukum","reformasi hukum","kerja sama hukum","stabilitas sosial"],
    "SDG17" => ["partnerships","global cooperation","international support","sustainable development","technology transfer","capacity building","international trade","debt sustainability","policy coherence","multi-stakeholder partnerships","data monitoring","statistical capacity","foreign aid","development assistance","development finance","global governance","international relations","policy coordination","international agreements","global south","south-south cooperation","north-south cooperation","triangular cooperation","development goals","international institutions","global partnership","resource mobilization","international collaboration","financial resources","knowledge sharing","digital cooperation","economic partnership","trade system","kemitraan","kerja sama internasional","pendanaan pembangunan","kapasitas nasional","perdagangan internasional","transfer teknologi","dukungan pembangunan","kebijakan global","kolaborasi multi-sektor","aliansi global","komitmen pembangunan","koordinasi antar negara","kemitraan publik-swasta","statistik pembangunan","sumber daya pembangunan","bantuan luar negeri","komunikasi global","data pembangunan","monitoring global","pelaporan SDG"],
];

// -----------------------------------------------------------------
// IMPACT INDICATORS & PATHWAYS (tidak berubah)
// -----------------------------------------------------------------
$IMPACT_INDICATORS = [
    'solution_words'    => ['solution','framework','model','approach','strategy','implementation','tool','method','solusi','kerangka','model','pendekatan','strategi','implementasi','alat','metode'],
    'policy_words'      => ['policy','regulation','governance','planning','management','program','initiative','kebijakan','regulasi','tata kelola','perencanaan','manajemen','program','inisiatif'],
    'outcome_words'     => ['impact','outcome','result','improvement','benefit','effect','change','reduction','dampak','hasil','peningkatan','manfaat','efek','perubahan','pengurangan'],
    'stakeholder_words' => ['community','stakeholder','participant','practitioner','policymaker','decision-maker','komunitas','pemangku kepentingan','peserta','praktisi','pembuat kebijakan','pengambil keputusan'],
    'evaluation_words'  => ['evaluation','assessment','monitoring','measurement','indicator','verification','validation','evaluasi','penilaian','pemantauan','pengukuran','indikator','verifikasi','validasi'],
];

$TRANSFORMATIVE_VERBS = ['develop','implement','improve','enhance','establish','strengthen','transform','create','innovate','solve','reduce','increase','optimize','facilitate','enable','mengembangkan','mengimplementasikan','meningkatkan','memperbaiki','membangun','memperkuat','mentransformasi','menciptakan','berinovasi','menyelesaikan','mengurangi','mengoptimalkan'];

$CONTRIBUTION_PATHWAYS = [
    'SDG1'  => ['poverty_reduction'=>['poverty reduction','poverty alleviation','income increase'],'social_protection'=>['social protection','safety net','social security'],'basic_services'=>['basic services','essential services','access to services']],
    'SDG2'  => ['food_security'=>['food security','food availability','food access'],'nutrition'=>['nutrition','malnutrition reduction','balanced diet'],'sustainable_agriculture'=>['sustainable agriculture','agroecology','pertanian berkelanjutan']],
    'SDG3'  => ['health_coverage'=>['health coverage','universal healthcare','akses kesehatan'],'disease_prevention'=>['disease prevention','epidemic control','pencegahan penyakit'],'well_being'=>['well-being','mental health','kesejahteraan']],
    'SDG4'  => ['quality_education'=>['quality education','education access','pendidikan berkualitas'],'lifelong_learning'=>['lifelong learning','skills development','pembelajaran seumur hidup'],'education_equity'=>['education equity','inclusive education','kesetaraan pendidikan']],
    'SDG5'  => ['gender_equality'=>['gender equality','women empowerment','kesetaraan gender'],'violence_prevention'=>['violence prevention','gender-based violence','pencegahan kekerasan'],'leadership_opportunities'=>['leadership opportunities','women in leadership','kesempatan kepemimpinan']],
    'SDG6'  => ['water_access'=>['water access','clean water','safe water','akses air'],'water_management'=>['water management','water conservation','pengelolaan air'],'sanitation'=>['sanitation','hygiene','sanitasi','kebersihan']],
    'SDG7'  => ['clean_energy'=>['clean energy','renewable energy','energi bersih'],'energy_access'=>['energy access','energy poverty','akses energi'],'energy_efficiency'=>['energy efficiency','energy conservation','efisiensi energi']],
    'SDG8'  => ['economic_growth'=>['economic growth','decent work','pertumbuhan ekonomi'],'employment'=>['employment','job creation','kesempatan kerja'],'labor_rights'=>['labor rights','worker protection','hak pekerja']],
    'SDG9'  => ['infrastructure'=>['infrastructure','resilient infrastructure','infrastruktur'],'industrialization'=>['industrialization','inclusive industrialization','industrialisasi'],'innovation'=>['innovation','research and development','inovasi']],
    'SDG10' => ['inequality_reduction'=>['inequality reduction','social inclusion','pengurangan kesenjangan'],'migration'=>['migration','safe migration','migrasi'],'financial_inclusion'=>['financial inclusion','access to finance','inklusi keuangan']],
    'SDG11' => ['sustainable_cities'=>['sustainable cities','urban planning','kota berkelanjutan'],'housing'=>['housing','affordable housing','perumahan'],'public_spaces'=>['public spaces','green spaces','ruang publik']],
    'SDG12' => ['sustainable_consumption'=>['sustainable consumption','responsible consumption','konsumsi berkelanjutan'],'waste_management'=>['waste management','recycling','pengelolaan sampah'],'circular_economy'=>['circular economy','resource efficiency','ekonomi sirkular']],
    'SDG13' => ['mitigation'=>['climate mitigation','emission reduction','carbon reduction'],'adaptation'=>['climate adaptation','climate resilience','adaptasi iklim'],'awareness'=>['climate awareness','climate education','kesadaran iklim']],
    'SDG14' => ['marine_conservation'=>['marine conservation','ocean health','konservasi laut'],'sustainable_fishing'=>['sustainable fishing','overfishing prevention','perikanan berkelanjutan'],'marine_pollution'=>['marine pollution','plastic pollution','polusi laut']],
    'SDG15' => ['biodiversity'=>['biodiversity','species protection','keanekaragaman hayati'],'land_restoration'=>['land restoration','combat desertification','restorasi lahan'],'forest_management'=>['forest management','deforestation prevention','pengelolaan hutan']],
    'SDG16' => ['peace'=>['peace','conflict resolution','perdamaian'],'justice'=>['justice','rule of law','keadilan'],'institutions'=>['institutions','accountability','lembaga']],
    'SDG17' => ['partnerships'=>['partnerships','global cooperation','kemitraan'],'capacity_building'=>['capacity building','technology transfer','pengembangan kapasitas'],'trade'=>['trade','fair trade','perdagangan']],
];

// =================================================================
// FUNGSI UTAMA – ROUTER
// =================================================================
function main() {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';

        // --- ORCID ---
        if (isset($_GET['orcid'])) {
            $orcid = trim($_GET['orcid']);
            // Strip full ORCID URL if supplied (e.g. https://orcid.org/0009-0007-2395-0980)
            if (preg_match('/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i', $orcid, $m)) {
                $orcid = $m[1];
            }
            if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
                throw new Exception('Format ORCID tidak valid. Gunakan format: XXXX-XXXX-XXXX-XXXX', 400);
            }

            $action = isset($_GET['action']) ? $_GET['action'] : 'full';

            switch ($action) {
                case 'init':
                    return handleOrcidInitRequest($orcid, $force_refresh);

                case 'batch':
                    $offset = max(0, (int)(isset($_GET['offset']) ? $_GET['offset'] : 0));
                    $limit  = min(10, max(1, (int)(isset($_GET['limit'])  ? $_GET['limit']  : BATCH_SIZE)));
                    return handleOrcidBatchRequest($orcid, $offset, $limit, $force_refresh);

                case 'summary':
                    return handleOrcidSummaryRequest($orcid);

                default: // 'full' – backward compatible
                    return handleOrcidRequest($orcid, $force_refresh);
            }
        }

        // --- DOI ---
        if (isset($_GET['doi'])) {
            return handleDoiRequest($_GET['doi'], $force_refresh);
        }

        throw new Exception('Parameter tidak valid. Gunakan ?orcid= atau ?doi=', 400);

    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 400);
        return [
            'status'  => 'error',
            'code'    => $e->getCode() ?: 400,
            'message' => $e->getMessage(),
            'usage'   => [
                'Init Peneliti'    => '?orcid=0000-0002-5152-9727&action=init',
                'Batch SDG'        => '?orcid=0000-0002-5152-9727&action=batch&offset=0&limit=3',
                'Ringkasan'        => '?orcid=0000-0002-5152-9727&action=summary',
                'Full (legacy)'    => '?orcid=0000-0002-5152-9727',
                'Artikel'          => '?doi=10.1234/example',
                'Refresh Cache'    => 'tambahkan &refresh=true',
            ],
            'timestamp'   => date('c'),
            'api_version' => 'v1.0.0',
        ];
    }
}

// =================================================================
// HANDLER BARU: SEQUENTIAL / BATCH PROCESSING
// =================================================================

/**
 * STEP 1 – Init: ambil info peneliti + daftar karya (tanpa analisis SDG)
 * Cache: orcid_init_<id>.json.gz
 */
function handleOrcidInitRequest($orcid, $force_refresh = false) {
    $cache_file = getCacheFilename('orcid_init', $orcid);

    if (!$force_refresh && file_exists($cache_file)) {
        $cached = readFromCache($cache_file);
        if ($cached !== false) {
            $cached['from_cache'] = true;
            return $cached;
        }
    }

    // Ambil data person, employments, educations & works dari ORCID API
    $person_data     = fetchOrcidPersonData($orcid);
    $employment_data = fetchOrcidEmployments($orcid);
    $education_data  = fetchOrcidEducations($orcid);
    $works_data      = fetchOrcidData($orcid);

    $name             = extractOrcidName($person_data);
    $institutions     = extractOrcidInstitutionsEnhanced($person_data, $employment_data);
    $bio              = extractOrcidBio($person_data);
    $emails           = extractOrcidEmails($person_data);
    $kw_tags          = extractOrcidKeywords($person_data);
    $external_ids     = extractOrcidExternalIds($person_data);
    $researcher_urls  = extractOrcidResearcherUrls($person_data);
    $all_affiliations = extractAllAffiliations($employment_data);
    $education_history = extractEducationHistory($education_data);

    // Kumpulkan stubs (judul + doi) tanpa analisis SDG
    $works_stubs = [];
    if (isset($works_data['group']) && is_array($works_data['group'])) {
        foreach ($works_data['group'] as $work) {
            $summary = isset($work['work-summary'][0]) ? $work['work-summary'][0] : null;
            if (!$summary) continue;

            $title = isset($summary['title']['title']['value']) ? $summary['title']['title']['value'] : '';
            if (empty($title)) continue;

            $doi      = extractDoi($summary);
            $put_code = isset($summary['put-code']) ? (string)$summary['put-code'] : null;
            $works_stubs[] = [
                'index'    => count($works_stubs),
                'title'    => $title,
                'doi'      => $doi,
                'put_code' => $put_code,
            ];
        }
    }

    $result = [
        'status'       => 'success',
        'action'       => 'init',
        'api_version'  => 'v1.0.0',
        'personal_info' => [
            'name'              => $name ?: 'Peneliti ' . $orcid,
            'institutions'      => $institutions,
            'orcid'             => $orcid,
            'bio'               => $bio,
            'emails'            => $emails,
            'keywords'          => $kw_tags,
            'external_ids'      => $external_ids,
            'researcher_urls'   => $researcher_urls,
            'affiliations'      => $all_affiliations,
            'education_history' => $education_history,
            'data_source'       => !empty($person_data) ? 'ORCID API' : 'Fallback',
        ],
        'total_works'  => count($works_stubs),
        'works_stubs'  => $works_stubs,
        'from_cache'   => false,
        'timestamp'    => date('c'),
    ];

    saveToCache($cache_file, $result);
    return $result;
}

/**
 * STEP 2 – Batch: proses karya[offset .. offset+limit] dengan analisis SDG penuh
 * Cache: orcid_batch_<id>_<offset>_<limit>.json.gz
 */
function handleOrcidBatchRequest($orcid, $offset, $limit, $force_refresh = false) {
    global $SDG_KEYWORDS, $CONFIG;

    // Ambil init cache untuk mendapatkan works_stubs
    $init_cache_file = getCacheFilename('orcid_init', $orcid);
    $init_data = readFromCache($init_cache_file);

    if ($init_data === false) {
        // Auto-init jika belum ada
        $init_data = handleOrcidInitRequest($orcid, $force_refresh);
    }

    $works_stubs = isset($init_data['works_stubs']) ? $init_data['works_stubs'] : [];
    $total_works = count($works_stubs);
    $batch_stubs = array_slice($works_stubs, $offset, $limit);

    if (empty($batch_stubs)) {
        return [
            'status'      => 'success',
            'action'      => 'batch',
            'api_version' => 'v1.0.0',
            'orcid'       => $orcid,
            'offset'      => $offset,
            'limit'       => $limit,
            'processed'   => 0,
            'total_works' => $total_works,
            'works'       => [],
            'is_done'     => true,
            'next_offset' => $offset,
            'timestamp'   => date('c'),
        ];
    }

    // Cek cache batch
    $batch_cache_id   = $orcid . '_' . $offset . '_' . $limit;
    $batch_cache_file = getCacheFilename('orcid_batch', $batch_cache_id);

    if (!$force_refresh && file_exists($batch_cache_file)) {
        $cached = readFromCache($batch_cache_file);
        if ($cached !== false) {
            $cached['from_cache'] = true;
            return $cached;
        }
    }

    // Proses setiap karya dalam batch
    $processed_works = [];

    foreach ($batch_stubs as $stub) {
        $title    = $stub['title'];
        $doi      = isset($stub['doi']) ? $stub['doi'] : null;
        $put_code = isset($stub['put_code']) ? $stub['put_code'] : null;

        // Ambil metadata lengkap via put-code (ORCID endpoint individual)
        $contributors  = [];
        $journal_title = '';
        $volume        = '';
        $issue         = '';
        $pages         = '';
        $pub_year      = null;
        $keywords      = [];
        $work_type     = '';
        $work_url      = '';

        if ($put_code) {
            try {
                $detail = fetchOrcidWorkDetail($orcid, $put_code);
                $contributors  = $detail['contributors']  ?? [];
                $journal_title = $detail['journal_title'] ?? '';
                $pub_year      = $detail['pub_year']      ?? null;
                $keywords      = $detail['keywords']      ?? [];
                $work_type     = $detail['work_type']     ?? '';
                $work_url      = $detail['url']           ?? '';
            } catch (Exception $e) {
                error_log("Batch: gagal fetch put-code $put_code: " . $e->getMessage());
            }
        }

        // Ambil abstrak + enrichment via Crossref/OpenAlex
        $abstract = '';
        if ($doi) {
            try {
                $doi_data = fetchDoiData($doi);

                // Abstract dari Crossref
                if (isset($doi_data['message']['abstract'])) {
                    $abstract = strip_tags($doi_data['message']['abstract']);
                }

                // Contributors dari Crossref jika ORCID tidak punya
                if (empty($contributors) && !empty($doi_data['message']['author'])) {
                    foreach ($doi_data['message']['author'] as $a) {
                        $n = trim(($a['given'] ?? '') . ' ' . ($a['family'] ?? ''));
                        if ($n) $contributors[] = ['name' => $n, 'orcid' => $a['ORCID'] ?? null];
                    }
                }

                // Journal dari Crossref jika kosong
                if (empty($journal_title) && !empty($doi_data['message']['container-title'][0])) {
                    $journal_title = $doi_data['message']['container-title'][0];
                }

                // Volume, issue, pages dari Crossref
                if (empty($volume) && !empty($doi_data['message']['volume'])) $volume = $doi_data['message']['volume'];
                if (empty($issue)  && !empty($doi_data['message']['issue']))  $issue  = $doi_data['message']['issue'];
                if (empty($pages)  && !empty($doi_data['message']['page']))   $pages  = $doi_data['message']['page'];

                // Year
                if (empty($pub_year) && !empty($doi_data['message']['published']['date-parts'][0][0])) {
                    $pub_year = (int)$doi_data['message']['published']['date-parts'][0][0];
                }

                // Keywords dari Crossref subjects
                if (empty($keywords) && !empty($doi_data['message']['subject'])) {
                    $keywords = $doi_data['message']['subject'];
                }

            } catch (Exception $e) {
                error_log("Batch: gagal ambil DOI $doi: " . $e->getMessage());
            }
        }

        // Fallback abstract via multi-source (OpenAlex, Semantic Scholar)
        if (empty($abstract) && $doi) {
            try { $abstract = fetchAbstractMultiSource($doi); } catch (Exception $e) {}
        }

        $full_text         = $title . ' ' . $abstract;
        $preprocessed_text = preprocessText($full_text);

        // Analisis SDG
        $sdg_analysis = [];
        foreach ($SDG_KEYWORDS as $sdg => $keywords) {
            $matched = false;
            foreach ($keywords as $keyword) {
                if (stripos($preprocessed_text, $keyword) !== false) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                $eval = evaluateSDGContribution($preprocessed_text, $sdg);
                if ($eval['score'] > $CONFIG['MIN_SCORE_THRESHOLD']) {
                    $sdg_analysis[$sdg] = $eval;
                }
            }
        }

        // Filter & sort
        $filtered_sdgs    = [];
        $sdg_confidence   = [];
        $contributor_types = [];
        $pathways         = [];

        foreach ($sdg_analysis as $sdg => $analysis) {
            if ($analysis['score'] < $CONFIG['CONFIDENCE_THRESHOLD']) continue;
            $filtered_sdgs[]           = $sdg;
            $sdg_confidence[$sdg]      = $analysis['score'];
            $contributor_types[$sdg]   = $analysis['contributor_type']['type'];
            if (!empty($analysis['impact_orientation']['dominant_pathway'])) {
                $pathways[$sdg] = $analysis['impact_orientation']['dominant_pathway'];
            }
        }

        arsort($sdg_confidence);
        if (count($filtered_sdgs) > $CONFIG['MAX_SDGS_PER_WORK']) {
            $sdg_confidence  = array_slice($sdg_confidence, 0, $CONFIG['MAX_SDGS_PER_WORK'], true);
            $filtered_sdgs   = array_keys($sdg_confidence);
            $contributor_types = array_intersect_key($contributor_types, $sdg_confidence);
            $pathways          = array_intersect_key($pathways, $sdg_confidence);
        }

        $processed_works[] = [
            'title'                 => $title,
            'doi'                   => $doi,
            'put_code'              => $put_code,
            'abstract'              => $abstract,
            'contributors'          => $contributors,
            'journal'               => $journal_title,
            'volume'                => $volume,
            'issue'                 => $issue,
            'pages'                 => $pages,
            'year'                  => $pub_year,
            'keywords'              => $keywords,
            'work_type'             => $work_type,
            'url'                   => $work_url,
            'sdgs'                  => $filtered_sdgs,
            'sdg_confidence'        => $sdg_confidence,
            'contributor_types'     => $contributor_types,
            'contribution_pathways' => $pathways,
            'detailed_analysis'     => $sdg_analysis,
        ];
    }

    $next_offset = $offset + $limit;
    $is_done     = ($next_offset >= $total_works);

    $result = [
        'status'      => 'success',
        'action'      => 'batch',
        'api_version' => 'v1.0.0',
        'orcid'       => $orcid,
        'offset'      => $offset,
        'limit'       => $limit,
        'processed'   => count($processed_works),
        'total_works' => $total_works,
        'works'       => $processed_works,
        'is_done'     => $is_done,
        'next_offset' => $next_offset,
        'from_cache'  => false,
        'timestamp'   => date('c'),
    ];

    saveToCache($batch_cache_file, $result);
    return $result;
}

/**
 * STEP 3 – Summary: agregasi semua batch cache → profil SDG peneliti
 * Dipanggil setelah semua batch selesai di sisi klien
 */
function handleOrcidSummaryRequest($orcid) {
    global $CONFIG;

    $init_cache_file = getCacheFilename('orcid_init', $orcid);
    $init_data       = readFromCache($init_cache_file);

    if ($init_data === false) {
        throw new Exception('Init data tidak ditemukan. Jalankan action=init terlebih dahulu.', 400);
    }

    $total_works = isset($init_data['total_works']) ? $init_data['total_works'] : 0;
    $limit       = BATCH_SIZE;

    $researcher_sdg_summary = [];
    $total_analyzed         = 0;
    $all_works              = [];

    // Baca semua batch cache dan agregasi
    for ($offset = 0; $offset < $total_works; $offset += $limit) {
        $batch_cache_id   = $orcid . '_' . $offset . '_' . $limit;
        $batch_cache_file = getCacheFilename('orcid_batch', $batch_cache_id);

        if (!file_exists($batch_cache_file)) continue;
        $batch_data = readFromCache($batch_cache_file);
        if ($batch_data === false) continue;

        foreach ($batch_data['works'] as $work) {
            $total_analyzed++;
            $all_works[] = $work;

            foreach ($work['detailed_analysis'] as $sdg => $analysis) {
                if ($analysis['score'] < $CONFIG['CONFIDENCE_THRESHOLD']) continue;

                if (!isset($researcher_sdg_summary[$sdg])) {
                    $researcher_sdg_summary[$sdg] = [
                        'work_count'           => 0,
                        'average_confidence'   => 0,
                        'high_confidence_works'=> 0,
                        'contributor_types'    => ['Active Contributor' => 0, 'Relevant Contributor' => 0, 'Discutor' => 0, 'Not Relevant' => 0],
                        'dominant_pathways'    => [],
                        'example_works'        => [],
                    ];
                }

                $s = &$researcher_sdg_summary[$sdg];
                $s['work_count']++;
                $s['average_confidence'] += $analysis['score'];

                $ct = $analysis['contributor_type']['type'];
                if (isset($s['contributor_types'][$ct])) $s['contributor_types'][$ct]++;

                if (!empty($analysis['impact_orientation']['dominant_pathway'])) {
                    $pw = $analysis['impact_orientation']['dominant_pathway'];
                    $s['dominant_pathways'][$pw] = ($s['dominant_pathways'][$pw] ?? 0) + 1;
                }

                if ($analysis['score'] >= $CONFIG['HIGH_CONFIDENCE_THRESHOLD']) {
                    $s['high_confidence_works']++;
                }

                if (count($s['example_works']) < 3) {
                    $s['example_works'][] = [
                        'title'          => $work['title'],
                        'doi'            => $work['doi'],
                        'confidence'     => $analysis['score'],
                        'contributor_type' => $ct,
                    ];
                }
                unset($s);
            }
        }
    }

    // Finalisasi rata-rata & urutan
    foreach ($researcher_sdg_summary as $sdg => &$summary) {
        if ($summary['work_count'] > 0) {
            $summary['average_confidence'] = round($summary['average_confidence'] / $summary['work_count'], 3);
        }
        if (!empty($summary['dominant_pathways'])) arsort($summary['dominant_pathways']);
    }
    unset($summary);

    uasort($researcher_sdg_summary, function($a, $b) { return $b['work_count'] - $a['work_count']; });

    // Bangun contributor profile
    $contributor_profile = [];
    foreach ($researcher_sdg_summary as $sdg => $summary) {
        $active   = $summary['contributor_types']['Active Contributor'];
        $relevant = $summary['contributor_types']['Relevant Contributor'];
        $total    = $summary['work_count'];

        $dominant_type = 'Discutor';
        if ($total > 0) {
            if (($active / $total) >= 0.3)               $dominant_type = 'Active Contributor';
            elseif (($active + $relevant) / $total >= 0.5) $dominant_type = 'Relevant Contributor';
        }

        $contributor_profile[$sdg] = [
            'dominant_type'   => $dominant_type,
            'work_distribution' => [
                'active_contributor'   => $active,
                'relevant_contributor' => $relevant,
                'discussant'           => $summary['contributor_types']['Discutor'],
            ],
            'active_contributor_percentage' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            'contribution_strength'         => determineContributionStrength($summary),
        ];
    }

    $summary_result = [
        'status'               => 'success',
        'action'               => 'summary',
        'api_version'          => 'v5.2.0',
        'orcid'                => $orcid,
        'personal_info'        => $init_data['personal_info'],
        'researcher_sdg_summary' => $researcher_sdg_summary,
        'contributor_profile'  => $contributor_profile,
        'total_works'          => $total_works,
        'total_works_analyzed' => $total_analyzed,
        'works'                => $all_works,
        'timestamp'            => date('c'),
    ];

    // Persist results to database (silently — never crash the API)
    persistOrcidResultsToDb($summary_result);

    // Strip works array from response to avoid bloating the JSON output
    unset($summary_result['works']);

    return $summary_result;
}

/**
 * Persist ORCID summary + individual works / SDG mappings to the SQLite database.
 * Wrapped in try/catch — if bootstrap.php was not loaded (direct API call), fails silently.
 */
function persistOrcidResultsToDb(array $summary): void {
    try {
        $db = getDb();
    } catch (Throwable $e) {
        // bootstrap not loaded — skip persistence silently
        return;
    }

    try {
        $orcid        = $summary['orcid'] ?? null;
        $name         = $summary['personal_info']['name'] ?? null;
        $institutions = json_encode($summary['personal_info']['institutions'] ?? []);
        $total_works  = $summary['total_works'] ?? 0;

        if (empty($orcid)) return;

        // ── Upsert researcher ─────────────────────────────────────
        $stmt = $db->prepare(
            'INSERT INTO researchers (orcid, name, institutions, total_works, last_fetched)
             VALUES (?, ?, ?, ?, datetime(\'now\'))
             ON CONFLICT(orcid) DO UPDATE SET
               name=excluded.name,
               institutions=excluded.institutions,
               total_works=excluded.total_works,
               last_fetched=excluded.last_fetched'
        );
        $stmt->execute([$orcid, $name, $institutions, $total_works]);

        // Get researcher ID
        $stmt = $db->prepare('SELECT id FROM researchers WHERE orcid=?');
        $stmt->execute([$orcid]);
        $researcher_id = $stmt->fetchColumn();
        if (!$researcher_id) return;

        // ── Upsert each work ──────────────────────────────────────
        foreach ($summary['works'] ?? [] as $work) {
            $title     = $work['title']     ?? null;
            $doi       = $work['doi']       ?? null;
            $abstract  = $work['abstract']  ?? null;
            $authors   = json_encode($work['contributors'] ?? []);
            $journal   = $work['journal']   ?? null;
            $volume    = $work['volume']    ?? null;
            $issue     = $work['issue']     ?? null;
            $pages     = $work['pages']     ?? null;
            $year      = isset($work['year']) ? (int)$work['year'] : null;
            $keywords  = json_encode($work['keywords'] ?? []);
            $work_type = $work['work_type'] ?? null;
            $url       = $work['url']       ?? null;
            $put_code  = $work['put_code']  ?? null;

            // Upsert work (keyed on put_code + researcher_id when available, else title)
            if ($put_code !== null) {
                $stmt = $db->prepare(
                    'INSERT INTO works
                       (researcher_id, put_code, title, doi, abstract, authors, journal,
                        volume, issue, pages, year, keywords, work_type, url)
                     SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                     WHERE NOT EXISTS (
                         SELECT 1 FROM works WHERE put_code=? AND researcher_id=?
                     )'
                );
                $stmt->execute([
                    $researcher_id, $put_code, $title, $doi, $abstract, $authors,
                    $journal, $volume, $issue, $pages, $year, $keywords, $work_type, $url,
                    $put_code, $researcher_id,
                ]);

                // Update existing record fields
                $stmt = $db->prepare(
                    'UPDATE works SET
                       title=?, doi=?, abstract=?, authors=?, journal=?, volume=?,
                       issue=?, pages=?, year=?, keywords=?, work_type=?, url=?
                     WHERE put_code=? AND researcher_id=?'
                );
                $stmt->execute([
                    $title, $doi, $abstract, $authors, $journal, $volume,
                    $issue, $pages, $year, $keywords, $work_type, $url,
                    $put_code, $researcher_id,
                ]);
            } else {
                // No put_code — insert only if title+researcher_id combo is new
                $stmt = $db->prepare(
                    'INSERT INTO works
                       (researcher_id, put_code, title, doi, abstract, authors, journal,
                        volume, issue, pages, year, keywords, work_type, url)
                     SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                     WHERE NOT EXISTS (
                         SELECT 1 FROM works WHERE title=? AND researcher_id=?
                     )'
                );
                $stmt->execute([
                    $researcher_id, null, $title, $doi, $abstract, $authors,
                    $journal, $volume, $issue, $pages, $year, $keywords, $work_type, $url,
                    $title, $researcher_id,
                ]);
            }

            // Get work ID
            if ($put_code !== null) {
                $stmt = $db->prepare('SELECT id FROM works WHERE put_code=? AND researcher_id=?');
                $stmt->execute([$put_code, $researcher_id]);
            } else {
                $stmt = $db->prepare('SELECT id FROM works WHERE title=? AND researcher_id=?');
                $stmt->execute([$title, $researcher_id]);
            }
            $work_id = $stmt->fetchColumn();
            if (!$work_id) continue;

            // ── Refresh SDG mappings for this work ────────────────
            $stmt = $db->prepare('DELETE FROM work_sdgs WHERE work_id=?');
            $stmt->execute([$work_id]);

            $sdg_analysis    = $work['detailed_analysis']  ?? [];
            $contributor_map = $work['contributor_types']  ?? [];

            foreach ($sdg_analysis as $sdg_code => $analysis) {
                $contributor_type = $analysis['contributor_type']['type'] ?? ($contributor_map[$sdg_code] ?? null);
                if ($contributor_type === 'Not Relevant') continue;

                $confidence_score = $analysis['score']             ?? null;
                $keyword_score    = $analysis['keyword_score']     ?? null;
                $similarity_score = $analysis['similarity_score']  ?? null;
                $causal_score     = $analysis['causal_score']      ?? null;

                $stmt = $db->prepare(
                    'INSERT INTO work_sdgs
                       (work_id, sdg_code, confidence_score, contributor_type,
                        keyword_score, similarity_score, causal_score)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $work_id, $sdg_code, $confidence_score, $contributor_type,
                    $keyword_score, $similarity_score, $causal_score,
                ]);
            }
        }
    } catch (Throwable $e) {
        error_log('[persistOrcidResultsToDb] DB error: ' . $e->getMessage());
    }
}

// =================================================================
// HANDLER LAMA (backward-compatible)
// =================================================================

function handleOrcidRequest($orcid, $force_refresh = false) {
    $cache_file = getCacheFilename('orcid', $orcid);
    if (!$force_refresh && file_exists($cache_file)) {
        $cached = readFromCache($cache_file);
        if ($cached !== false) {
            if (!isset($cached['personal_info']) || empty($cached['personal_info'])) {
                $cached['personal_info'] = ['name' => 'Peneliti ' . $orcid, 'institutions' => [], 'orcid' => $orcid];
            }
            $cached['cache_info'] = ['from_cache' => true, 'cache_date' => date('c', filemtime($cache_file))];
            return $cached;
        }
    }

    $person_data = fetchOrcidPersonData($orcid);
    $works_data  = fetchOrcidData($orcid);
    $result      = processOrcidData($orcid, $works_data, $person_data);

    if (!isset($result['personal_info']) || empty($result['personal_info']['name'])) {
        $result['personal_info'] = ['name' => 'Peneliti ' . $orcid, 'institutions' => [], 'orcid' => $orcid, 'data_source' => 'Fallback'];
    }

    saveToCache($cache_file, $result);
    $result['cache_info'] = ['from_cache' => false, 'cache_date' => date('c')];
    return $result;
}

function handleDoiRequest($doi, $force_refresh = false) {
    $doi = trim($doi);
    if (empty($doi)) throw new Exception('DOI tidak boleh kosong', 400);

    // Bersihkan prefix URL doi.org dan doi: jika ada
    $clean_doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $doi);
    $clean_doi = preg_replace('/^doi:/i', '', $clean_doi);
    $clean_doi = trim($clean_doi);

    // Validasi: DOI valid wajib diawali dengan 10.NNNN/
    if (!preg_match('/^10\.\d{4,}\//', $clean_doi)) {
        throw new Exception(
            'Input bukan DOI valid. DOI harus mengandung "doi.org" atau diawali dengan "10.xxxx/". Input: ' . htmlspecialchars($doi, ENT_QUOTES),
            400
        );
    }
    $doi = $clean_doi;

    $cache_file = getCacheFilename('article', $doi);
    if (!$force_refresh && file_exists($cache_file)) {
        $cached = readFromCache($cache_file);
        if ($cached !== false) {
            $cached['cache_info'] = ['from_cache' => true, 'cache_date' => date('c', filemtime($cache_file))];
            return $cached;
        }
    }

    $data   = fetchDoiData($doi);
    $result = processDoiData($doi, $data);
    saveToCache($cache_file, $result);
    $result['cache_info'] = ['from_cache' => false, 'cache_date' => date('c')];
    return $result;
}

// =================================================================
// FUNGSI PENGAMBILAN DATA
// =================================================================

function fetchOrcidData($orcid) {
    $url = "https://pub.orcid.org/v3.0/{$orcid}/works?pageSize=50";
    $ch  = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL           => $url,
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_HTTPHEADER    => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT=> 5,
        CURLOPT_TIMEOUT       => 15,
    ]);
    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $error    = curl_error($ch);
    curl_close($ch);
    if ($errno) throw new Exception('Gagal mengambil data ORCID: ' . $error, 500);
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Data ORCID tidak valid', 500);
    return $data;
}

function fetchOrcidPersonData($orcid) {
    $url = "https://pub.orcid.org/v3.0/{$orcid}/person";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_HTTPHEADER    => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT=> 5,
        CURLOPT_TIMEOUT       => 10,
    ]);
    $response  = curl_exec($ch);
    $errno     = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno || $http_code != 200) return [];
    $data = json_decode($response, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : [];
}

function fetchOrcidEmployments($orcid) {
    $url = "https://pub.orcid.org/v3.0/{$orcid}/employments";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 6,
    ]);
    $response  = curl_exec($ch);
    $errno     = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno || $http_code != 200) return [];
    $data = json_decode($response, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : [];
}

function fetchOrcidEducations($orcid) {
    $url = "https://pub.orcid.org/v3.0/{$orcid}/educations";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 6,
    ]);
    $response  = curl_exec($ch);
    $errno     = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno || $http_code != 200) return [];
    $data = json_decode($response, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : [];
}

/**
 * Format date array from ORCID (year/month/day nested) to YYYY-MM-DD string.
 */
function formatOrcidDateParts($date_array) {
    if (!is_array($date_array)) return null;
    $parts = [];
    if (!empty($date_array['year']['value']))  $parts[] = $date_array['year']['value'];
    if (!empty($date_array['month']['value'])) $parts[] = str_pad($date_array['month']['value'], 2, '0', STR_PAD_LEFT);
    if (!empty($date_array['day']['value']))   $parts[] = str_pad($date_array['day']['value'],   2, '0', STR_PAD_LEFT);
    return !empty($parts) ? implode('-', $parts) : null;
}

function extractOrcidExternalIds($person_data) {
    $external_ids = [];
    if (!empty($person_data['external-identifiers']['external-identifier'])) {
        foreach ($person_data['external-identifiers']['external-identifier'] as $ext_id) {
            $external_ids[] = [
                'type'  => isset($ext_id['external-id-type'])        ? $ext_id['external-id-type']        : null,
                'value' => isset($ext_id['external-id-value'])        ? $ext_id['external-id-value']        : null,
                'url'   => isset($ext_id['external-id-url']['value']) ? $ext_id['external-id-url']['value'] : null,
            ];
        }
    }
    return $external_ids;
}

function extractOrcidResearcherUrls($person_data) {
    $urls = [];
    if (!empty($person_data['researcher-urls']['researcher-url'])) {
        foreach ($person_data['researcher-urls']['researcher-url'] as $u) {
            $urls[] = [
                'name' => isset($u['url-name'])     ? $u['url-name']     : null,
                'url'  => isset($u['url']['value']) ? $u['url']['value'] : null,
            ];
        }
    }
    return $urls;
}

/**
 * Returns all employments with is_current flag (no end-date = current).
 */
function extractAllAffiliations($employment_data) {
    $affiliations = [];
    if (!empty($employment_data['affiliation-group'])) {
        foreach ($employment_data['affiliation-group'] as $group) {
            $summary = isset($group['summaries'][0]['employment-summary'])
                ? $group['summaries'][0]['employment-summary'] : null;
            if (!$summary) continue;
            $org = isset($summary['organization']['name']) ? trim($summary['organization']['name']) : '';
            if (strlen($org) < 2) continue;
            $affiliations[] = [
                'type'         => 'employment',
                'organization' => $org,
                'department'   => isset($summary['department-name']) ? $summary['department-name'] : null,
                'role'         => isset($summary['role-title'])      ? $summary['role-title']      : null,
                'start_date'   => formatOrcidDateParts(isset($summary['start-date']) ? $summary['start-date'] : null),
                'end_date'     => formatOrcidDateParts(isset($summary['end-date'])   ? $summary['end-date']   : null),
                'is_current'   => empty($summary['end-date']),
                'address'      => [
                    'city'    => isset($summary['organization']['address']['city'])    ? $summary['organization']['address']['city']    : null,
                    'region'  => isset($summary['organization']['address']['region'])  ? $summary['organization']['address']['region']  : null,
                    'country' => isset($summary['organization']['address']['country']) ? $summary['organization']['address']['country'] : null,
                ],
            ];
        }
    }
    return $affiliations;
}

function extractEducationHistory($education_data) {
    $educations = [];
    if (!empty($education_data['affiliation-group'])) {
        foreach ($education_data['affiliation-group'] as $group) {
            $summary = isset($group['summaries'][0]['education-summary'])
                ? $group['summaries'][0]['education-summary'] : null;
            if (!$summary) continue;
            $org = isset($summary['organization']['name']) ? trim($summary['organization']['name']) : '';
            if (strlen($org) < 2) continue;
            $educations[] = [
                'organization' => $org,
                'department'   => isset($summary['department-name']) ? $summary['department-name'] : null,
                'degree'       => isset($summary['role-title'])      ? $summary['role-title']      : null,
                'start_date'   => formatOrcidDateParts(isset($summary['start-date']) ? $summary['start-date'] : null),
                'end_date'     => formatOrcidDateParts(isset($summary['end-date'])   ? $summary['end-date']   : null),
                'address'      => [
                    'city'    => isset($summary['organization']['address']['city'])    ? $summary['organization']['address']['city']    : null,
                    'region'  => isset($summary['organization']['address']['region'])  ? $summary['organization']['address']['region']  : null,
                    'country' => isset($summary['organization']['address']['country']) ? $summary['organization']['address']['country'] : null,
                ],
            ];
        }
    }
    return $educations;
}

function extractOrcidBio($person_data) {
    if (empty($person_data) || !is_array($person_data)) return null;
    return isset($person_data['biography']['content']) ? trim($person_data['biography']['content']) : null;
}

function extractOrcidEmails($person_data) {
    $emails = [];
    if (empty($person_data['emails']['email'])) return $emails;
    foreach ($person_data['emails']['email'] as $entry) {
        if (!empty($entry['email'])) $emails[] = $entry['email'];
    }
    return $emails;
}

function extractOrcidKeywords($person_data) {
    $keywords = [];
    if (empty($person_data['keywords']['keyword'])) return $keywords;
    foreach ($person_data['keywords']['keyword'] as $kw) {
        if (!empty($kw['content'])) $keywords[] = $kw['content'];
    }
    return $keywords;
}

/**
 * Multi-source abstract fetching: CrossRef → OpenAlex → Semantic Scholar
 * Returns full (untruncated) abstract string.
 */
function fetchAbstractMultiSource($doi) {
    if (empty($doi)) return '';
    $clean = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', trim($doi));

    // 1. CrossRef
    try {
        $url = "https://api.crossref.org/works/" . urlencode($clean);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => 'SDG-Classifier/5.2 (mailto:wizdam@sangia.org)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200 && $resp) {
            $d = json_decode($resp, true);
            if (!empty($d['message']['abstract'])) return strip_tags($d['message']['abstract']);
        }
    } catch (Exception $e) {}

    // 2. OpenAlex (inverted index → reconstructed)
    try {
        $url = "https://api.openalex.org/works/doi:" . urlencode($clean);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => 'SDG-Classifier/5.2 (mailto:wizdam@sangia.org)',
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200 && $resp) {
            $d = json_decode($resp, true);
            if (!empty($d['abstract_inverted_index'])) {
                $words = [];
                foreach ($d['abstract_inverted_index'] as $word => $positions) {
                    foreach ($positions as $pos) $words[$pos] = $word;
                }
                ksort($words);
                $abstract = implode(' ', $words);
                if (!empty($abstract)) return $abstract;
            }
        }
    } catch (Exception $e) {}

    // 3. Semantic Scholar
    try {
        $url = "https://api.semanticscholar.org/graph/v1/paper/DOI:" . urlencode($clean) . "?fields=abstract";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200 && $resp) {
            $d = json_decode($resp, true);
            if (!empty($d['abstract'])) return $d['abstract'];
        }
    } catch (Exception $e) {}

    return '';
}

/**
 * Ambil detail lengkap satu karya via ORCID put-code endpoint
 * GET /v3.0/{orcid}/work/{put-code}
 */
function fetchOrcidWorkDetail($orcid, $put_code) {
    $url = "https://pub.orcid.org/v3.0/{$orcid}/work/{$put_code}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($errno || $httpCode !== 200) return [];
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) return [];

    // Contributors
    $contributors = [];
    if (isset($data['contributors']['contributor'])) {
        foreach ($data['contributors']['contributor'] as $c) {
            $name  = $c['credit-name']['value'] ?? null;
            $orcid_path = $c['contributor-orcid']['path'] ?? null;
            if ($name) $contributors[] = ['name' => $name, 'orcid' => $orcid_path];
        }
    }

    // Journal title
    $journal_title = isset($data['journal-title']['value']) ? $data['journal-title']['value'] : '';

    // Publication year
    $pub_year = null;
    if (isset($data['publication-date']['year']['value'])) {
        $pub_year = (int)$data['publication-date']['year']['value'];
    }

    // Keywords
    $keywords = [];
    if (isset($data['keywords']['keyword'])) {
        foreach ($data['keywords']['keyword'] as $kw) {
            if (!empty($kw['content'])) $keywords[] = $kw['content'];
        }
    }

    // Work type
    $work_type = $data['type'] ?? '';

    // URL
    $work_url = isset($data['url']['value']) ? $data['url']['value'] : '';

    return [
        'contributors'  => $contributors,
        'journal_title' => $journal_title,
        'pub_year'      => $pub_year,
        'keywords'      => $keywords,
        'work_type'     => $work_type,
        'url'           => $work_url,
    ];
}

function fetchDoiData($doi) {
    $url     = "https://api.crossref.org/works/" . urlencode($doi);
    $maxTry  = 3;
    $delay   = 2;
    $lastErr = '';

    for ($attempt = 0; $attempt < $maxTry; $attempt++) {
        if ($attempt > 0) sleep($delay * $attempt);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_USERAGENT      => 'SDG-Classifier/5.2 (mailto:wizdam@sangia.org)',
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 12,
        ]);
        $raw       = curl_exec($ch);
        $errno     = curl_errno($ch);
        $errStr    = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize= curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $ctypeRaw  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($errno) { $lastErr = 'cURL error: ' . $errStr; continue; }

        // Rate limit – tunggu lalu retry
        if ($httpCode === 429) { $lastErr = 'Crossref rate limit (429)'; continue; }

        // DOI tidak ditemukan
        if ($httpCode === 404) throw new Exception('DOI tidak ditemukan di Crossref (404)', 404);

        if ($httpCode !== 200) throw new Exception("Crossref HTTP $httpCode untuk DOI: $doi", 500);

        // Validasi Content-Type: harus JSON
        if ($ctypeRaw && stripos($ctypeRaw, 'json') === false) {
            throw new Exception('Crossref tidak mengembalikan JSON (Content-Type: ' . $ctypeRaw . ')', 500);
        }

        $body = substr($raw, $headerSize);

        // Validasi karakter pertama respons
        $firstChar = ltrim($body)[0] ?? '';
        if ($firstChar !== '{' && $firstChar !== '[') {
            throw new Exception('Crossref mengembalikan respons non-JSON: ' . substr($body, 0, 80), 500);
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON Crossref tidak valid: ' . json_last_error_msg(), 500);
        }
        return $data;
    }

    throw new Exception('Gagal mengambil data Crossref setelah ' . $maxTry . ' percobaan. ' . $lastErr, 500);
}

function fetchAbstractFromAlternativeSource($doi) {
    // Coba OpenAlex terlebih dahulu (lebih lengkap)
    $openAlexUrl = "https://api.openalex.org/works/doi:" . urlencode($doi);
    $ch = curl_init($openAlexUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'SDG-Classifier/5.2 (mailto:wizdam@sangia.org)',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // OpenAlex menyimpan abstract sebagai inverted index
            if (!empty($data['abstract_inverted_index'])) {
                $inverted = $data['abstract_inverted_index'];
                $words = [];
                foreach ($inverted as $word => $positions) {
                    foreach ($positions as $pos) {
                        $words[$pos] = $word;
                    }
                }
                ksort($words);
                $abstract = implode(' ', $words);
                if (!empty($abstract)) return $abstract;
            }
        }
    }

    // Fallback: Semantic Scholar
    $ssUrl = "https://api.semanticscholar.org/v1/paper/" . urlencode($doi);
    $ch = curl_init($ssUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) return '';
    $data = json_decode($response, true);
    return (isset($data['abstract']) && !empty($data['abstract'])) ? $data['abstract'] : '';
}

// =================================================================
// PEMROSESAN DATA ORCID / DOI (legacy full mode)
// =================================================================

function processOrcidData($orcid, $works_data, $person_data) {
    global $SDG_KEYWORDS, $CONFIG;

    $name         = extractOrcidName($person_data);
    $institutions = extractOrcidInstitutions($person_data);
    if (empty($name)) $name = "Peneliti " . $orcid;

    $processed_works        = [];
    $researcher_sdg_summary = [];
    $contributor_profile    = [];

    if (isset($works_data['group']) && is_array($works_data['group'])) {
        foreach ($works_data['group'] as $work) {
            $summary = isset($work['work-summary'][0]) ? $work['work-summary'][0] : null;
            if (!$summary) continue;

            $title = isset($summary['title']['title']['value']) ? $summary['title']['title']['value'] : '';
            $doi   = extractDoi($summary);
            if (empty($title)) continue;

            $abstract = '';
            if ($doi) {
                try {
                    $doi_data = fetchDoiData($doi);
                    if (isset($doi_data['message']['abstract'])) {
                        $abstract = strip_tags($doi_data['message']['abstract']);
                    }
                    if (empty($abstract)) $abstract = fetchAbstractMultiSource($doi);
                } catch (Exception $e) { /* continue */ }
            }

            $full_text        = $title . ' ' . $abstract;
            $preprocessed     = preprocessText($full_text);
            $sdg_analysis     = [];

            foreach ($SDG_KEYWORDS as $sdg => $keywords) {
                $matched = false;
                foreach ($keywords as $kw) {
                    if (stripos($preprocessed, $kw) !== false) { $matched = true; break; }
                }
                if ($matched) {
                    $eval = evaluateSDGContribution($preprocessed, $sdg);
                    if ($eval['score'] > $CONFIG['MIN_SCORE_THRESHOLD']) $sdg_analysis[$sdg] = $eval;
                }
            }

            $filtered      = [];
            $confidence    = [];
            $ctypes        = [];
            $pathways      = [];

            foreach ($sdg_analysis as $sdg => $a) {
                if ($a['score'] < $CONFIG['CONFIDENCE_THRESHOLD']) continue;
                $filtered[]       = $sdg;
                $confidence[$sdg] = $a['score'];
                $ctypes[$sdg]     = $a['contributor_type']['type'];
                if (!empty($a['impact_orientation']['dominant_pathway'])) $pathways[$sdg] = $a['impact_orientation']['dominant_pathway'];
            }

            arsort($confidence);
            if (count($filtered) > $CONFIG['MAX_SDGS_PER_WORK']) {
                $confidence = array_slice($confidence, 0, $CONFIG['MAX_SDGS_PER_WORK'], true);
                $filtered   = array_keys($confidence);
                $ctypes     = array_intersect_key($ctypes, $confidence);
                $pathways   = array_intersect_key($pathways, $confidence);
            }

            $processed_works[] = [
                'title' => $title, 'doi' => $doi, 'abstract' => $abstract,
                'sdgs' => $filtered, 'sdg_confidence' => $confidence,
                'contributor_types' => $ctypes, 'contribution_pathways' => $pathways,
                'detailed_analysis' => $sdg_analysis,
            ];

            foreach ($sdg_analysis as $sdg => $analysis) {
                if ($analysis['score'] < $CONFIG['CONFIDENCE_THRESHOLD']) continue;
                if (!isset($researcher_sdg_summary[$sdg])) {
                    $researcher_sdg_summary[$sdg] = ['work_count' => 0,'average_confidence' => 0,'high_confidence_works' => 0,'contributor_types' => ['Active Contributor' => 0,'Relevant Contributor' => 0,'Discutor' => 0,'Not Relevant' => 0],'dominant_pathways' => [],'example_works' => []];
                }
                $s = &$researcher_sdg_summary[$sdg];
                $s['work_count']++;
                $s['average_confidence'] += $analysis['score'];
                $ct = $analysis['contributor_type']['type'];
                if (isset($s['contributor_types'][$ct])) $s['contributor_types'][$ct]++;
                if (!empty($analysis['impact_orientation']['dominant_pathway'])) {
                    $pw = $analysis['impact_orientation']['dominant_pathway'];
                    $s['dominant_pathways'][$pw] = ($s['dominant_pathways'][$pw] ?? 0) + 1;
                }
                if ($analysis['score'] >= $CONFIG['HIGH_CONFIDENCE_THRESHOLD']) $s['high_confidence_works']++;
                if (count($s['example_works']) < 3) $s['example_works'][] = ['title' => $title,'doi' => $doi,'confidence' => $analysis['score'],'contributor_type' => $ct];
                unset($s);
            }
        }
    }

    foreach ($researcher_sdg_summary as $sdg => &$sum) {
        if ($sum['work_count'] > 0) $sum['average_confidence'] = round($sum['average_confidence'] / $sum['work_count'], 3);
        if (!empty($sum['dominant_pathways'])) arsort($sum['dominant_pathways']);
    }
    unset($sum);

    uasort($researcher_sdg_summary, function($a, $b) { return $b['work_count'] - $a['work_count']; });

    foreach ($researcher_sdg_summary as $sdg => $sum) {
        $active   = $sum['contributor_types']['Active Contributor'];
        $relevant = $sum['contributor_types']['Relevant Contributor'];
        $total    = $sum['work_count'];
        $dominant_type = 'Discutor';
        if ($total > 0) {
            if (($active / $total) >= 0.3)               $dominant_type = 'Active Contributor';
            elseif (($active + $relevant) / $total >= 0.5) $dominant_type = 'Relevant Contributor';
        }
        $contributor_profile[$sdg] = [
            'dominant_type' => $dominant_type,
            'work_distribution' => ['active_contributor' => $active,'relevant_contributor' => $relevant,'discussant' => $sum['contributor_types']['Discutor']],
            'active_contributor_percentage' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            'contribution_strength' => determineContributionStrength($sum),
        ];
    }

    return [
        'personal_info'         => ['name' => $name,'institutions' => $institutions,'orcid' => $orcid,'data_source' => !empty($person_data) ? 'ORCID API' : 'Fallback'],
        'contributor_profile'   => $contributor_profile,
        'researcher_sdg_summary'=> $researcher_sdg_summary,
        'works'                 => $processed_works,
        'status'                => 'success',
        'api_version'           => 'v1.0.0',
        'timestamp'             => date('c'),
    ];
}

function processDoiData($doi, $data) {
    global $SDG_KEYWORDS, $CONFIG;

    $title    = isset($data['message']['title'][0]) ? $data['message']['title'][0] : '';
    $abstract = '';
    if (isset($data['message']['abstract'])) $abstract = strip_tags($data['message']['abstract']);
    if (empty($abstract)) {
        try { $abstract = fetchAbstractMultiSource($doi); } catch (Exception $e) {}
    }

    $full_text    = $title . ' ' . $abstract;
    $preprocessed = preprocessText($full_text);

    $authors = [];
    if (isset($data['message']['author'])) {
        foreach ($data['message']['author'] as $a) {
            $n = trim((isset($a['given']) ? $a['given'] . ' ' : '') . (isset($a['family']) ? $a['family'] : ''));
            if ($n) $authors[] = $n;
        }
    }

    $sdg_analysis = [];
    foreach (array_keys($SDG_KEYWORDS) as $sdg) {
        $matched = false;
        foreach ($SDG_KEYWORDS[$sdg] as $kw) {
            if (stripos($preprocessed, $kw) !== false) { $matched = true; break; }
        }
        if ($matched) {
            $eval = evaluateSDGContribution($preprocessed, $sdg);
            if ($eval['score'] > $CONFIG['MIN_SCORE_THRESHOLD']) $sdg_analysis[$sdg] = $eval;
        }
    }

    $filtered   = [];
    $confidence = [];
    $ctypes     = [];
    $pathways   = [];

    foreach ($sdg_analysis as $sdg => $a) {
        if ($a['score'] < $CONFIG['CONFIDENCE_THRESHOLD']) continue;
        $filtered[]       = $sdg;
        $confidence[$sdg] = $a['score'];
        $ctypes[$sdg]     = $a['contributor_type']['type'];
        if (!empty($a['impact_orientation']['dominant_pathway'])) $pathways[$sdg] = $a['impact_orientation']['dominant_pathway'];
    }

    arsort($confidence);
    if (count($filtered) > $CONFIG['MAX_SDGS_PER_WORK']) {
        $confidence = array_slice($confidence, 0, $CONFIG['MAX_SDGS_PER_WORK'], true);
        $filtered   = array_keys($confidence);
    }

    $year_parts     = isset($data['message']['published']['date-parts'][0])
                        ? $data['message']['published']['date-parts'][0]
                        : (isset($data['message']['published-print']['date-parts'][0])
                            ? $data['message']['published-print']['date-parts'][0] : []);
    $published_date = !empty($year_parts) ? implode('-', $year_parts) : '';
    $year           = !empty($year_parts[0]) ? (int)$year_parts[0] : null;

    return [
        'doi'                  => $doi,
        'title'                => $title,
        'abstract'             => $abstract,
        'authors'              => $authors,
        'journal'              => isset($data['message']['container-title'][0]) ? $data['message']['container-title'][0] : '',
        'published_date'       => $published_date,
        'year'                 => $year,
        'sdgs'                 => $filtered,
        'sdg_confidence'       => $confidence,
        'contributor_types'    => $ctypes,
        'contribution_pathways'=> $pathways,
        'detailed_analysis'    => $sdg_analysis,
        'api_version'          => 'v1.0.0',
        'status'               => 'success',
        'timestamp'            => date('c'),
    ];
}

// =================================================================
// FUNGSI ANALISIS SDG (tidak berubah dari v1.0.0)
// =================================================================

function evaluateSDGContribution($text, $sdg) {
    global $CONFIG, $MEMORY_CACHE;
    $cacheKey = md5($text . '_' . $sdg . '_contribution_v4');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $keywordScore    = isset(scoreSDGs($text)[$sdg]) ? scoreSDGs($text)[$sdg] : 0;
    $similarityScore = isset(calculateSDGSimilarity($text)[$sdg]) ? calculateSDGSimilarity($text)[$sdg] : 0;
    $substantiveResult = analyzeSubstantiveContribution($text, $sdg);
    $substantiveScore  = $substantiveResult['score'] ?? 0;
    $causalResult      = detectCausalRelationship($text, $sdg);
    $causalScore       = $causalResult['score'] ?? 0;
    $impactResult      = evaluateImpactOrientation($text, $sdg);
    $impactScore       = $impactResult['score'] ?? 0;

    $weights = ['KEYWORD_WEIGHT' => $CONFIG['KEYWORD_WEIGHT'], 'SIMILARITY_WEIGHT' => $CONFIG['SIMILARITY_WEIGHT'], 'SUBSTANTIVE_WEIGHT' => $CONFIG['SUBSTANTIVE_WEIGHT'], 'CAUSAL_WEIGHT' => $CONFIG['CAUSAL_WEIGHT']];
    if (strlen($text) < 100) { $weights = ['KEYWORD_WEIGHT' => 0.40, 'SIMILARITY_WEIGHT' => 0.40, 'SUBSTANTIVE_WEIGHT' => 0.10, 'CAUSAL_WEIGHT' => 0.10]; }

    $combinedScore = ($keywordScore * $weights['KEYWORD_WEIGHT']) + ($similarityScore * $weights['SIMILARITY_WEIGHT']) + ($substantiveScore * $weights['SUBSTANTIVE_WEIGHT']) + ($causalScore * $weights['CAUSAL_WEIGHT']);

    $confidenceLevel = 'Low';
    if ($combinedScore > $CONFIG['HIGH_CONFIDENCE_THRESHOLD'])  $confidenceLevel = 'High';
    elseif ($combinedScore > $CONFIG['CONFIDENCE_THRESHOLD'])   $confidenceLevel = 'Middle';

    $contributorType = determineContributorType($combinedScore, $causalScore, $impactScore);

    $evidence = [];
    global $SDG_KEYWORDS;
    $matchedKeywords = [];
    foreach ($SDG_KEYWORDS[$sdg] as $keyword) {
        if (stripos($text, $keyword) !== false) {
            $ctx = extractKeywordContext($text, $keyword);
            if (!empty($ctx)) { $matchedKeywords[] = ['keyword' => $keyword, 'context' => $ctx]; }
            if (count($matchedKeywords) >= 3) break;
        }
    }
    if (!empty($matchedKeywords)) $evidence['keyword_matches'] = $matchedKeywords;
    if (!empty($causalResult['evidence']))  $evidence['causal_relationship'] = $causalResult['evidence'];
    if (!empty($impactResult['evidence']))  $evidence['impact_orientation']  = $impactResult['evidence'];

    $result = [
        'score'            => round($combinedScore, 3),
        'confidence_level' => $confidenceLevel,
        'contributor_type' => $contributorType,
        'components'       => ['keyword_score' => round($keywordScore,3),'similarity_score' => round($similarityScore,3),'substantive_score' => round($substantiveScore,3),'causal_score' => round($causalScore,3),'impact_score' => round($impactScore,3)],
        'impact_orientation' => ['score' => $impactResult['score'],'level' => $impactResult['level'],'dominant_pathway' => $impactResult['dominant_pathway'] ?? ''],
        'evidence'         => $evidence,
        'weights_used'     => $weights,
    ];

    $MEMORY_CACHE[$cacheKey] = $result;
    return $result;
}

function evaluateImpactOrientation($text, $sdg) {
    global $IMPACT_INDICATORS, $TRANSFORMATIVE_VERBS, $CONTRIBUTION_PATHWAYS, $MEMORY_CACHE;
    $cacheKey = md5($text . '_' . $sdg . '_impact');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $text = strtolower($text);
    $impact_scores = [];
    $evidence = [];

    foreach ($IMPACT_INDICATORS as $category => $indicators) {
        $score = 0;
        foreach ($indicators as $indicator) {
            if (stripos($text, $indicator) !== false) {
                $score += 1;
                foreach ($TRANSFORMATIVE_VERBS as $verb) {
                    if (stripos($text, $verb . ' ' . $indicator) !== false || stripos($text, $indicator . ' ' . $verb) !== false) {
                        $score += 0.5;
                        break;
                    }
                }
            }
        }
        $impact_scores[$category] = min(1, $score / (count($indicators) * 0.5));
    }

    $pathway_scores = [];
    if (isset($CONTRIBUTION_PATHWAYS[$sdg])) {
        foreach ($CONTRIBUTION_PATHWAYS[$sdg] as $pathway => $indicators) {
            $score = 0;
            foreach ($indicators as $indicator) {
                if (stripos($text, $indicator) !== false) $score += 1;
            }
            $pathway_scores[$pathway] = min(1, $score / max(1, count($indicators)));
        }
    }

    $transformative_patterns = ['this research contributes to','we propose','we develop','this study aims to','the results show','the findings indicate','we found that','implications for','penelitian ini berkontribusi','kami mengusulkan','kami mengembangkan','studi ini bertujuan','hasil menunjukkan','temuan mengindikasikan'];
    $transformative_score = 0;
    foreach ($transformative_patterns as $pattern) {
        if (stripos($text, $pattern) !== false) $transformative_score += 0.2;
    }
    $transformative_score = min(1, $transformative_score);

    $total_impact_score = 0;
    if (!empty($impact_scores))  $total_impact_score += (array_sum($impact_scores) / max(1, count($impact_scores))) * 0.5;
    if (!empty($pathway_scores)) $total_impact_score += (array_sum($pathway_scores) / max(1, count($pathway_scores))) * 0.3;
    $total_impact_score += $transformative_score * 0.2;
    $final_impact_score = $total_impact_score / 1.0;

    $impact_level = 'Low';
    if ($final_impact_score > 0.6)      $impact_level = 'High';
    elseif ($final_impact_score > 0.3)  $impact_level = 'Middle';

    $dominant_pathway = '';
    $highest = 0;
    foreach ($pathway_scores as $pw => $sc) {
        if ($sc > $highest) { $highest = $sc; $dominant_pathway = $pw; }
    }

    $result = ['score' => round($final_impact_score, 3), 'level' => $impact_level, 'components' => ['impact_indicators' => $impact_scores, 'contribution_pathways' => $pathway_scores, 'transformative_language' => $transformative_score], 'dominant_pathway' => $dominant_pathway, 'evidence' => $evidence];
    $MEMORY_CACHE[$cacheKey] = $result;
    return $result;
}

function detectCausalRelationship($text, $sdg) {
    global $CAUSAL_PATTERNS, $SDG_KEYWORDS, $MEMORY_CACHE, $TRANSFORMATIVE_VERBS;
    $cacheKey = md5($text . '_' . $sdg . '_causal_v4');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    if (!is_array($CAUSAL_PATTERNS)) $CAUSAL_PATTERNS = ['contributes to','supports','advances','helps achieve','improves','untuk','agar','supaya','mendukung','membantu'];

    $expandedPatterns = array_merge($CAUSAL_PATTERNS, ['for','to','can','will','could','toward','reduce','increase','improve','prevent','ensure','provide','allow','enable','help','support','untuk','guna','agar','supaya','dapat','akan','bisa','mengurangi','meningkatkan','memperbaiki','mencegah','memastikan','menyediakan','memungkinkan','membantu','mendukung']);

    $relevantKeywords = array_slice($SDG_KEYWORDS[$sdg] ?? [], 0, 10);
    $score    = 0;
    $evidences = [];

    foreach ($expandedPatterns as $pattern) {
        foreach ($relevantKeywords as $keyword) {
            if (stripos($text, $pattern . ' ' . $keyword) !== false) {
                $score += 0.3;
                $ctx = extractKeywordContext($text, $pattern . ' ' . $keyword, 150);
                if (!empty($ctx)) $evidences[] = ['type' => 'direct_causality', 'pattern' => $pattern . ' ' . $keyword, 'context' => $ctx];
            }
            if (stripos($text, $keyword . ' ' . $pattern) !== false) {
                $score += 0.3;
            }
        }
    }

    if (is_array($TRANSFORMATIVE_VERBS)) {
        foreach ($TRANSFORMATIVE_VERBS as $verb) {
            foreach ($relevantKeywords as $keyword) {
                $vp = stripos($text, $verb);
                $kp = stripos($text, $keyword);
                if ($vp !== false && $kp !== false && abs($vp - $kp) < 50) {
                    $score += 0.25;
                    break;
                }
            }
        }
    }

    $normalized = min(1, $score);
    if (strlen($text) < 100 && $normalized < 0.1 && hasSDGConcept($text, $sdg)) $normalized = max($normalized, 0.1);

    $result = ['score' => $normalized, 'evidence' => array_slice($evidences, 0, 3)];
    $MEMORY_CACHE[$cacheKey] = $result;
    return $result;
}

function analyzeSubstantiveContribution($text, $sdg) {
    global $MEMORY_CACHE;
    $cacheKey = md5($text . '_' . $sdg . '_substantive');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $SUBSTANTIVE_INDICATORS = [
        'solution_words'  => ['solution','strategy','approach','intervention','policy','program','solusi','strategi','pendekatan','intervensi','kebijakan','program'],
        'impact_words'    => ['impact','effect','outcome','result','evaluation','assessment','dampak','efek','hasil','evaluasi','penilaian'],
        'methodology_words' => ['survey','interview','analysis','study','research','method','survei','wawancara','analisis','studi','penelitian','metode'],
    ];

    $scores = [];
    foreach ($SUBSTANTIVE_INDICATORS as $category => $indicators) {
        $categoryScore = 0;
        foreach ($indicators as $indicator) {
            if (stripos($text, $indicator) !== false) $categoryScore++;
        }
        $divisor = count($indicators) * 0.5;
        $scores[$category] = min(1, $divisor > 0 ? $categoryScore / $divisor : 0);
    }

    $result = ['score' => !empty($scores) ? array_sum($scores) / count($scores) : 0, 'components' => $scores];
    $MEMORY_CACHE[$cacheKey] = $result;
    return $result;
}

function determineContributorType($combinedScore, $causalScore, $impactScore) {
    global $CONFIG;
    $contributionScore = ($combinedScore * 0.5) + ($causalScore * 0.3) + ($impactScore * 0.2);
    if ($contributionScore >= $CONFIG['ACTIVE_CONTRIBUTOR_THRESHOLD'] && $causalScore >= 0.3 && $impactScore >= 0.3) {
        return ['type' => 'Active Contributor', 'description' => 'Research with substantive contribution to SDG', 'score' => round($contributionScore, 3)];
    } elseif ($contributionScore >= $CONFIG['RELEVANT_CONTRIBUTOR_THRESHOLD']) {
        return ['type' => 'Relevant Contributor', 'description' => 'Research with clear relevance to SDGs', 'score' => round($contributionScore, 3)];
    } elseif ($contributionScore >= $CONFIG['DISCUSSANT_THRESHOLD']) {
        return ['type' => 'Discutor', 'description' => 'Research discusses SDG-related themes', 'score' => round($contributionScore, 3)];
    } else {
        return ['type' => 'Not Relevant', 'description' => 'Research does not show sufficient relevance', 'score' => round($contributionScore, 3)];
    }
}

function determineContributionStrength($summary) {
    $score = 0;
    if ($summary['work_count'] >= 10) $score += 3;
    elseif ($summary['work_count'] >= 5) $score += 2;
    elseif ($summary['work_count'] >= 3) $score += 1;
    $hcr = $summary['high_confidence_works'] / max(1, $summary['work_count']);
    if ($hcr >= 0.5) $score += 3; elseif ($hcr >= 0.3) $score += 2; elseif ($hcr >= 0.1) $score += 1;
    $ar = $summary['contributor_types']['Active Contributor'] / max(1, $summary['work_count']);
    if ($ar >= 0.5) $score += 4; elseif ($ar >= 0.3) $score += 3; elseif ($ar >= 0.2) $score += 2; elseif ($ar >= 0.1) $score += 1;
    if (!empty($summary['dominant_pathways'])) {
        $pv = array_values($summary['dominant_pathways']); rsort($pv);
        $dr = $pv[0] / $summary['work_count'];
        if ($dr >= 0.6) $score += 2; elseif ($dr >= 0.3) $score += 1;
    }
    if ($score >= 10) return 'Very Strong';
    if ($score >= 7)  return 'Strong';
    if ($score >= 4)  return 'Moderate';
    return 'Low';
}

// =================================================================
// FUNGSI ANALISIS SDG DASAR
// =================================================================

function scoreSDGs($text) {
    global $SDG_KEYWORDS, $MEMORY_CACHE;
    $cacheKey = md5($text . '_score');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $text  = strtolower($text);
    $scores = [];
    $wordFreq = array_count_values(str_word_count($text, 1));

    foreach ($SDG_KEYWORDS as $sdg => $keywords) {
        $count = 0;
        foreach ($keywords as $keyword) {
            if (strpos($keyword, ' ') !== false) $count += substr_count($text, strtolower($keyword));
            elseif (isset($wordFreq[strtolower($keyword)])) $count += $wordFreq[strtolower($keyword)];
        }
        if ($count > 0) $scores[$sdg] = $count;
    }

    $total = array_sum($scores);
    if ($total > 0) foreach ($scores as $sdg => $v) $scores[$sdg] = round($v / $total, 3);
    arsort($scores);

    $MEMORY_CACHE[$cacheKey] = $scores;
    return $scores;
}

function calculateSDGSimilarity($text) {
    global $SDG_KEYWORDS, $MEMORY_CACHE;
    $cacheKey = md5($text . '_similarity');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $text  = strtolower($text);
    $scores = [];
    static $sdgVectors = [];
    $text_vector = createTextVector($text);

    foreach ($SDG_KEYWORDS as $sdg => $keywords) {
        if (!isset($sdgVectors[$sdg])) $sdgVectors[$sdg] = createTextVector(implode(' ', $keywords));
        $sim = calculateCosineSimilarity($text_vector, $sdgVectors[$sdg]);
        if ($sim > 0) $scores[$sdg] = $sim;
    }

    arsort($scores);
    $MEMORY_CACHE[$cacheKey] = $scores;
    return $scores;
}

function createTextVector($text) {
    global $MEMORY_CACHE;
    $cacheKey = md5($text . '_vector');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $words  = preg_split('/\s+/', $text);
    $vector = [];
    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) > 2) $vector[$word] = ($vector[$word] ?? 0) + 1;
    }
    $MEMORY_CACHE[$cacheKey] = $vector;
    return $vector;
}

function calculateCosineSimilarity($vector1, $vector2) {
    if (count($vector1) > count($vector2)) { $t = $vector1; $vector1 = $vector2; $vector2 = $t; }
    $dot = $mag1 = $mag2 = 0;
    foreach ($vector1 as $dim => $v1) { $v2 = $vector2[$dim] ?? 0; $dot += $v1 * $v2; $mag1 += $v1 * $v1; }
    foreach ($vector2 as $v2) $mag2 += $v2 * $v2;
    $mag1 = sqrt($mag1); $mag2 = sqrt($mag2);
    if ($mag1 == 0 || $mag2 == 0) return 0;
    return round($dot / ($mag1 * $mag2), 3);
}

// =================================================================
// FUNGSI UTILITAS
// =================================================================

function getSdgMainTerm($sdg) {
    $terms = ['SDG1'=>'poverty','SDG2'=>'hunger','SDG3'=>'health','SDG4'=>'education','SDG5'=>'gender','SDG6'=>'water','SDG7'=>'energy','SDG8'=>'work','SDG9'=>'industry','SDG10'=>'inequality','SDG11'=>'cities','SDG12'=>'consumption','SDG13'=>'climate','SDG14'=>'ocean','SDG15'=>'land','SDG16'=>'peace','SDG17'=>'partnership'];
    return $terms[$sdg] ?? '';
}

function hasSDGConcept($text, $sdg) {
    $conceptMap = ['SDG1'=>['extreme poverty','social protection','economic inclusion','kemiskinan ekstrim','perlindungan sosial','inklusi ekonomi'],'SDG2'=>['food security','sustainable agriculture','nutrition improvement','ketahanan pangan','pertanian berkelanjutan','perbaikan gizi'],'SDG3'=>['maternal health','child mortality','communicable diseases','kesehatan ibu','kematian anak','penyakit menular'],'SDG4'=>['quality education','lifelong learning','educational infrastructure','pendidikan berkualitas','pembelajaran seumur hidup','infrastruktur pendidikan'],'SDG5'=>['gender equality','women empowerment','gender-based violence','kesetaraan gender','pemberdayaan perempuan','kekerasan berbasis gender'],'SDG6'=>['water management','water conservation','sanitation facilities','pengelolaan air','konservasi air','fasilitas sanitasi'],'SDG7'=>['renewable energy','energy efficiency','clean cooking','energi terbarukan','efisiensi energi','memasak bersih'],'SDG8'=>['economic growth','decent work','youth employment','pertumbuhan ekonomi','pekerjaan layak','lapangan kerja pemuda'],'SDG9'=>['industrial innovation','sustainable infrastructure','scientific research','inovasi industri','infrastruktur berkelanjutan','penelitian ilmiah'],'SDG10'=>['reduced inequalities','social inclusion','migration policies','pengurangan ketimpangan','inklusi sosial','kebijakan migrasi'],'SDG11'=>['sustainable cities','urban planning','public transport','kota berkelanjutan','tata kota','transportasi umum'],'SDG12'=>['responsible consumption','waste reduction','circular economy','konsumsi bertanggung jawab','pengurangan limbah','ekonomi sirkular'],'SDG13'=>['climate action','carbon reduction','climate resilience','aksi iklim','pengurangan karbon','ketahanan iklim'],'SDG14'=>['marine pollution','ocean acidification','sustainable fishing','polusi laut','asidifikasi laut','perikanan berkelanjutan'],'SDG15'=>['biodiversity','forest conservation','land degradation','keanekaragaman hayati','konservasi hutan','degradasi lahan'],'SDG16'=>['peacebuilding','access to justice','corruption reduction','pembangunan perdamaian','akses keadilan','pengurangan korupsi'],'SDG17'=>['global partnership','technology transfer','capacity building','kemitraan global','transfer teknologi','pengembangan kapasitas']];
    if (!isset($conceptMap[$sdg])) return false;
    foreach ($conceptMap[$sdg] as $concept) { if (stripos($text, $concept) !== false) return true; }
    return false;
}

function preprocessText($text) {
    global $MEMORY_CACHE;
    $cacheKey = md5($text . '_preprocessed');
    if (isset($MEMORY_CACHE[$cacheKey])) return $MEMORY_CACHE[$cacheKey];

    $text = strtolower(strip_tags($text));
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $text = trim(preg_replace('/\s+/', ' ', $text));

    $MEMORY_CACHE[$cacheKey] = $text;
    return $text;
}

function extractKeywordContext($text, $keyword, $contextLength = 100) {
    $position = stripos($text, $keyword);
    if ($position === false) return '';
    $start   = max(0, $position - intval($contextLength / 2));
    $length  = strlen($keyword) + $contextLength;
    $context = substr($text, $start, $length);
    if ($start > 0) $context = '...' . $context;
    if ($start + $length < strlen($text)) $context .= '...';
    $context = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<strong>$1</strong>', $context);
    return $context;
}

function extractDoi($summary) {
    if (!isset($summary['external-ids']['external-id'])) return null;
    foreach ($summary['external-ids']['external-id'] as $id) {
        if (isset($id['external-id-type']) && strtolower($id['external-id-type']) === 'doi' && !empty($id['external-id-value'])) {
            return $id['external-id-value'];
        }
    }
    return null;
}

function extractOrcidName($person_data) {
    if (empty($person_data) || !is_array($person_data)) return 'Unknown Researcher';
    if (isset($person_data['name']['credit-name']['value'])) return $person_data['name']['credit-name']['value'];
    $name = '';
    if (isset($person_data['name']['given-names']['value'])) $name .= $person_data['name']['given-names']['value'] . ' ';
    if (isset($person_data['name']['family-name']['value'])) $name .= $person_data['name']['family-name']['value'];
    return !empty(trim($name)) ? trim($name) : 'Unknown Researcher';
}

function extractOrcidInstitutions($person_data) {
    return extractOrcidInstitutionsEnhanced($person_data, []);
}

/**
 * Enhanced institution extraction using /employments endpoint data.
 * Prefers current (no end-date) affiliations, falls back to all.
 */
function extractOrcidInstitutionsEnhanced($person_data, $employment_data) {
    $institutions = [];

    // Primary: parse affiliation-group from /employments endpoint
    if (!empty($employment_data['affiliation-group'])) {
        $current = [];
        $all     = [];
        foreach ($employment_data['affiliation-group'] as $group) {
            $summary = isset($group['summaries'][0]['employment-summary'])
                ? $group['summaries'][0]['employment-summary'] : null;
            if (!$summary) continue;
            $org = isset($summary['organization']['name']) ? trim($summary['organization']['name']) : '';
            if (strlen($org) < 3) continue;
            $all[] = $org;
            // Current = no end date
            if (empty($summary['end-date'])) $current[] = $org;
        }
        $institutions = !empty($current) ? $current : $all;
    }

    // Fallback: /person endpoint legacy structure (rarely populated)
    if (empty($institutions) && !empty($person_data['employments']['employment-summary'])) {
        foreach ($person_data['employments']['employment-summary'] as $emp) {
            if (isset($emp['organization']['name'])) {
                $n = trim($emp['organization']['name']);
                if (strlen($n) > 2) $institutions[] = $n;
            }
        }
    }

    return array_values(array_unique($institutions));
}

// =================================================================
// FUNGSI CACHE
// =================================================================

function saveToCache($filename, $data) {
    global $CACHE_DIR;
    if (!is_dir($CACHE_DIR)) mkdir($CACHE_DIR, 0755, true);
    file_put_contents($filename, gzencode(json_encode($data), 9));
}

function readFromCache($filename) {
    global $CONFIG;
    if (!file_exists($filename)) return false;
    if ((time() - filemtime($filename)) > $CONFIG['CACHE_TTL']) return false;
    $compressed = file_get_contents($filename);
    if ($compressed === false) return false;
    $json = gzdecode($compressed);
    if ($json === false) return false;
    $data = json_decode($json, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : false;
}

/**
 * Mendukung tipe cache baru: orcid_init dan orcid_batch
 */
function getCacheFilename($type, $id) {
    global $CACHE_DIR;
    $unique_code = substr(md5($id . '_v52'), 0, 8);

    switch ($type) {
        case 'orcid_init':
            return $CACHE_DIR . '/orcid_init_' . $unique_code . '_' . preg_replace('/[^0-9\-X]/', '', $id) . '.json.gz';

        case 'orcid_batch':
            // $id sudah berisi orcid + offset + limit, bersihkan karakter aneh
            $safe_id = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);
            return $CACHE_DIR . '/orcid_batch_' . $unique_code . '_' . $safe_id . '.json.gz';

        case 'orcid':
            return $CACHE_DIR . '/orcid_' . $unique_code . '_' . $id . '.json.gz';

        case 'article':
            $safe_doi = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $id);
            return $CACHE_DIR . '/article_' . $safe_doi . '_' . $unique_code . '.json.gz';
    }
    return false;
}

// =================================================================
// EKSEKUSI
// =================================================================
try {
    $result = main();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Internal error: ' . $e->getMessage(), 'timestamp' => date('c'), 'api_version' => 'v1.0.0'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}