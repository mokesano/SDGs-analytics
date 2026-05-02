-- ============================================================
-- FAQ Seed Data — Wizdam Chatbot Knowledge Base
-- Bilingual: English (en_US) and Indonesian (id_ID)
-- ============================================================

-- ── GENERAL FAQ (English) ────────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('What is Wizdam SDG Classifier?', 'Wizdam is an AI-powered platform that analyzes research publications and classifies them according to the 17 UN Sustainable Development Goals (SDGs). Our system uses advanced natural language processing to identify how your research contributes to global sustainability goals.', 'general', 'wizdam,sdg,classifier,about,what', 'en_US', 1),

('How does the SDG classification work?', 'Our AI analyzes your research using four components: (1) Keywords matching - identifies direct SDG-related terms (30%), (2) Semantic similarity - measures how close your text is to SDG themes using SciBERT (30%), (3) Substantive analysis - evaluates depth of SDG engagement (20%), and (4) Causal analysis - detects evidence of causal relationships with SDG outcomes (20%). The combined score determines your contribution level.', 'sdg', 'classification,how,work,analysis,method', 'en_US', 2),

('What are the contributor types?', 'We classify researchers into four types based on their SDG contribution: Active Contributor (strong evidence across multiple analysis components), Relevant Contributor (moderate evidence with clear SDG connection), Discutor (mentions SDG topics but limited substantive engagement), and Not Relevant (minimal or no SDG connection).', 'sdg', 'contributor,types,active,relevant,discutor,meaning', 'en_US', 3),

('Is this service free?', 'Yes! Wizdam SDG Classifier is completely free for researchers, institutions, and publishers. We believe in open science and making SDG tracking accessible to everyone.', 'platform', 'free,cost,price,payment', 'en_US', 4);

-- ── ORCID FAQ (English) ──────────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('What is an ORCID ID?', 'ORCID (Open Researcher and Contributor ID) is a unique 16-digit identifier for researchers. It looks like: 0000-0002-1825-0097. ORCID helps distinguish you from other researchers and ensures your work is properly attributed.', 'orcid', 'orcid,what,identifier,id', 'en_US', 10),

('How do I get an ORCID ID?', 'You can register for a free ORCID iD at https://orcid.org/register. The process takes less than 30 seconds. Once registered, you''ll receive a 16-digit ID that you can use across all research platforms.', 'orcid', 'orcid,get,register,create,obtain', 'en_US', 11),

('What is the correct ORCID format?', 'ORCID must be in the format: 0000-0000-0000-0000 (four groups of four digits separated by hyphens). The last character can be a digit or ''X''. Example: 0000-0002-1825-0097. You can also paste the full URL: https://orcid.org/0000-0002-1825-0097', 'orcid', 'orcid,format,valid,correct,example', 'en_US', 12),

('Why is my ORCID invalid?', 'Common ORCID validation errors: (1) Wrong number of digits (must be exactly 16), (2) Missing hyphens (should be in groups of 4), (3) Invalid checksum (last digit doesn''t match ISNI check algorithm), (4) Using spaces instead of hyphens. Double-check your ORCID on the official ORCID website.', 'orcid', 'orcid,invalid,error,wrong,problem', 'en_US', 13);

-- ── DOI FAQ (English) ────────────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('What is a DOI?', 'DOI (Digital Object Identifier) is a permanent identifier for academic documents. It looks like: 10.1038/nature12373 or 10.1371/journal.pone.0123456. DOIs ensure your publication can always be found, even if URLs change.', 'doi', 'doi,what,identifier,permanent', 'en_US', 20),

('How do I find a DOI?', 'DOIs are usually found on the first page of academic articles, in the citation information, or in the article metadata. You can also search for your article on Crossref.org or the publisher''s website. Most modern articles have a DOI assigned.', 'doi', 'doi,find,locate,get,search', 'en_US', 21),

('What is the correct DOI format?', 'A DOI typically starts with ''10.'' followed by a publisher code and article identifier. Examples: 10.1038/nature12373, 10.1371/journal.pone.0123456. You can enter just the DOI or paste the full URL like https://doi.org/10.1038/nature12373', 'doi', 'doi,format,valid,correct,example', 'en_US', 22),

('Can I analyze multiple DOIs at once?', 'Currently, our platform analyzes one DOI at a time. For batch analysis of multiple publications, we recommend using the researcher''s ORCID ID instead, which will automatically analyze all their indexed works.', 'doi', 'doi,multiple,batch,bulk,several', 'en_US', 23);

-- ── TECHNICAL FAQ (English) ──────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('How long does analysis take?', 'Analysis time depends on the number of publications. A single DOI takes 5-10 seconds. An ORCID with 50 publications may take 2-5 minutes. Large profiles (100+ works) can take up to 10 minutes. You can safely navigate away - results are saved automatically.', 'technical', 'time,long,duration,wait,slow', 'en_US', 30),

('What if analysis fails?', 'If analysis fails, check: (1) Valid input format (ORCID/DOI), (2) Internet connection, (3) The publication exists in ORCID/Crossref databases. If problems persist, wait a few minutes and try again. Some publishers have rate limits that may cause temporary failures.', 'technical', 'fail,error,problem,issue,not working', 'en_US', 31),

('Is my data secure?', 'Absolutely. All analysis is performed securely. We cache results temporarily (7 days) to improve performance, but we don''t share your data with third parties. ORCID data is public by default, and we only access what you authorize.', 'technical', 'secure,security,privacy,data,protection', 'en_US', 32);

-- ── UMUM FAQ (Indonesian) ────────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('Apa itu Wizdam SDG Classifier?', 'Wizdam adalah platform berbasis AI yang menganalisis publikasi penelitian dan mengklasifikasikannya sesuai 17 Tujuan Pembangunan Berkelanjutan (SDG) PBB. Sistem kami menggunakan pemrosesan bahasa alami canggih untuk mengidentifikasi bagaimana penelitian Anda berkontribusi pada tujuan keberlanjutan global.', 'general', 'wizdam,sdg,classifier,tentang,apa', 'id_ID', 1),

('Bagaimana cara kerja klasifikasi SDG?', 'AI kami menganalisis penelitian Anda menggunakan empat komponen: (1) Pencocokan kata kunci - mengidentifikasi istilah terkait SDG langsung (30%), (2) Kesamaan semantik - mengukur kedekatan teks Anda dengan tema SDG menggunakan SciBERT (30%), (3) Analisis substantif - mengevaluasi kedalaman keterlibatan SDG (20%), dan (4) Analisis kausal - mendeteksi bukti hubungan kausal dengan hasil SDG (20%). Skor gabungan menentukan tingkat kontribusi Anda.', 'sdg', 'klasifikasi,cara,kerja,analisis,metode', 'id_ID', 2),

('Apa saja jenis kontributor?', 'Kami mengklasifikasikan peneliti menjadi empat jenis berdasarkan kontribusi SDG mereka: Active Contributor (bukti kuat di beberapa komponen analisis), Relevant Contributor (bukti moderat dengan koneksi SDG jelas), Discutor (menyebut topik SDG tapi keterlibatan substantif terbatas), dan Not Relevant (koneksi SDG minimal atau tidak ada).', 'sdg', 'kontributor,jenis,active,relevant,discutor,arti', 'id_ID', 3),

('Apakah layanan ini gratis?', 'Ya! Wizdam SDG Classifier sepenuhnya gratis untuk peneliti, institusi, dan penerbit. Kami percaya pada sains terbuka dan membuat pelacakan SDG dapat diakses oleh semua orang.', 'platform', 'gratis,biaya,harga,bayar', 'id_ID', 4);

-- ── ORCID FAQ (Indonesian) ───────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('Apa itu ORCID ID?', 'ORCID (Open Researcher and Contributor ID) adalah identifikasi unik 16 digit untuk peneliti. Bentuknya seperti: 0000-0002-1825-0097. ORCID membantu membedakan Anda dari peneliti lain dan memastikan karya Anda dikaitkan dengan benar.', 'orcid', 'orcid,apa,identitas,id', 'id_ID', 10),

('Bagaimana cara mendapatkan ORCID ID?', 'Anda bisa mendaftar ORCID iD gratis di https://orcid.org/register. Prosesnya kurang dari 30 detik. Setelah terdaftar, Anda akan menerima ID 16 digit yang bisa digunakan di semua platform penelitian.', 'orcid', 'orcid,dapat,daftar,buat,peroleh', 'id_ID', 11),

('Apa format ORCID yang benar?', 'ORCID harus dalam format: 0000-0000-0000-0000 (empat kelompok empat digit dipisahkan tanda hubung). Karakter terakhir bisa angka atau ''X''. Contoh: 0000-0002-1825-0097. Anda juga bisa menempelkan URL lengkap: https://orcid.org/0000-0002-1825-0097', 'orcid', 'orcid,format,valid,benar,contoh', 'id_ID', 12),

('Mengapa ORCID saya tidak valid?', 'Error validasi ORCID umum: (1) Jumlah digit salah (harus tepat 16), (2) Tanda hubung hilang (harus dalam kelompok 4), (3) Checksum tidak valid (digit terakhir tidak cocok dengan algoritma ISNI), (4) Menggunakan spasi bukan tanda hubung. Periksa ulang ORCID Anda di website resmi ORCID.', 'orcid', 'orcid,tidak valid,error,salah,masalah', 'id_ID', 13);

-- ── DOI FAQ (Indonesian) ─────────────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('Apa itu DOI?', 'DOI (Digital Object Identifier) adalah identifikasi permanen untuk dokumen akademik. Bentuknya seperti: 10.1038/nature12373 atau 10.1371/journal.pone.0123456. DOI memastikan publikasi Anda selalu dapat ditemukan, bahkan jika URL berubah.', 'doi', 'doi,apa,identitas,permanen', 'id_ID', 20),

('Bagaimana cara menemukan DOI?', 'DOI biasanya ditemukan di halaman pertama artikel akademik, dalam informasi sitasi, atau di metadata artikel. Anda juga bisa mencari artikel Anda di Crossref.org atau website penerbit. Sebagian besar artikel modern memiliki DOI.', 'doi', 'doi,temukan,cari,dapat,lokasi', 'id_ID', 21),

('Apa format DOI yang benar?', 'DOI biasanya dimulai dengan ''10.'' diikuti kode penerbit dan identifikasi artikel. Contoh: 10.1038/nature12373, 10.1371/journal.pone.0123456. Anda bisa memasukkan DOI saja atau menempelkan URL lengkap seperti https://doi.org/10.1038/nature12373', 'doi', 'doi,format,valid,benar,contoh', 'id_ID', 22),

('Bisakah menganalisis beberapa DOI sekaligus?', 'Saat ini, platform kami menganalisis satu DOI dalam satu waktu. Untuk analisis batch beberapa publikasi, kami sarankan menggunakan ORCID ID peneliti, yang akan otomatis menganalisis semua karya mereka yang terindeks.', 'doi', 'doi,beberapa,batch,bulk,sekali gus', 'id_ID', 23);

-- ── TECHNICAL FAQ (Indonesian) ───────────────────────────────────────────
INSERT INTO faq (question, answer, category, keywords, locale, order_num) VALUES
('Berapa lama analisis berlangsung?', 'Waktu analisis tergantung jumlah publikasi. Satu DOI membutuhkan 5-10 detik. ORCID dengan 50 publikasi mungkin butuh 2-5 menit. Profil besar (100+ karya) bisa sampai 10 menit. Anda bisa navigasi ke halaman lain - hasil disimpan otomatis.', 'technical', 'waktu,lama,durasi,tunggu,lambat', 'id_ID', 30),

('Bagaimana jika analisis gagal?', 'Jika analisis gagal, periksa: (1) Format input valid (ORCID/DOI), (2) Koneksi internet, (3) Publikasi ada di database ORCID/Crossref. Jika masalah berlanjut, tunggu beberapa menit dan coba lagi. Beberapa penerbit memiliki batas rate yang bisa menyebabkan kegagalan sementara.', 'technical', 'gagal,error,masalah,isu,tidak berhasil', 'id_ID', 31),

('Apakah data saya aman?', 'Tentu saja. Semua analisis dilakukan dengan aman. Kami menyimpan hasil sementara (7 hari) untuk meningkatkan performa, tapi kami tidak membagikan data Anda ke pihak ketiga. Data ORCID bersifat publik secara default, dan kami hanya mengakses apa yang Anda izinkan.', 'technical', 'aman,keamanan,privasi,data,perlindungan', 'id_ID', 32);
