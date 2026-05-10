<?php
/**
 * includes/sdg_subject_mapping.php
 * Maps Scopus ASJC subject area strings → UN SDG codes.
 */

const SUBJECT_SDG_MAP = [
    'SDG1'  => ['poverty','inequality','social science','development studies','public administration','welfare'],
    'SDG2'  => ['food','agriculture','agronomy','crop','nutrition','veterinary','aquaculture','fisheries','soil','horticulture','food science'],
    'SDG3'  => ['medicine','health','pharmacology','nursing','public health','epidemiology','immunology','oncology','dentistry','neurology','psychiatry','biochemistry','clinical','surgery','cardiology','infectious','toxicology','rehabilitation','virology','pathology','radiology'],
    'SDG4'  => ['education','learning','pedagogy','curriculum','teaching','literacy','training','university','school','e-learning','higher education','special education'],
    'SDG5'  => ['gender','women','feminist','reproductive','human rights','violence against','discrimination','sexual'],
    'SDG6'  => ['water','sanitation','hydrology','wastewater','water treatment','water resource','groundwater','watershed','water quality','water supply'],
    'SDG7'  => ['energy','renewable','solar','wind','biofuel','electricity','fuel cell','photovoltaic','power generation','geothermal','biomass','hydrogen','battery'],
    'SDG8'  => ['economics','business','management','labour','employment','finance','trade','entrepreneurship','industrial','economic growth','tourism','hospitality'],
    'SDG9'  => ['engineering','technology','innovation','infrastructure','manufacturing','transportation','materials science','civil engineering','automation','robotics','nanotechnology','aerospace','construction'],
    'SDG10' => ['inequality','migration','refugee','social protection','developing countries','inclusive','marginalized'],
    'SDG11' => ['urban','cities','housing','planning','architecture','transport','smart city','disaster risk','heritage','resilience','municipality'],
    'SDG12' => ['consumption','recycling','waste','circular economy','supply chain','resource efficiency','sustainable production','packaging','textile'],
    'SDG13' => ['climate','carbon','greenhouse','emission','global warming','adaptation','mitigation','atmosphere','weather','meteorology','climate change'],
    'SDG14' => ['ocean','marine','fisheries','coral','coastal','sea','aquatic','blue economy','oceanography','seawater','tidal'],
    'SDG15' => ['ecology','biodiversity','forest','land use','deforestation','conservation','species','terrestrial','wildlife','habitat','afforestation','desertification'],
    'SDG16' => ['peace','justice','governance','law','corruption','human rights','security','conflict','democracy','institution','accountability','transparency'],
    'SDG17' => ['partnership','cooperation','development aid','diplomacy','international','global governance','official development','multilateral'],
];

function mapSubjectsToSdgs(array $subjects): array {
    $matched = [];
    foreach ($subjects as $subject) {
        $s = strtolower(is_array($subject) ? ($subject['name'] ?? ($subject['subject'] ?? '')) : (string)$subject);
        foreach (SUBJECT_SDG_MAP as $sdg => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($s, $kw) !== false) {
                    $matched[$sdg] = true;
                    break;
                }
            }
        }
    }
    ksort($matched);
    return array_keys($matched);
}
