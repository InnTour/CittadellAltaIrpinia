<?php
/**
 * Populate borough data extracted from MD reports.
 * Run once: GET /api/admin/populate-borghi-from-reports.php?token=API_TOKEN
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../export/_generate_functions.php';

$token = $_GET['token'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

header('Content-Type: text/plain; charset=utf-8');

$db = getDB();

$boroughs = [
    'andretta' => [
        'description' => 'Borgo dell\'Alta Irpinia con continuità insediativa documentata dal Paleolitico (32.000 a.C.) e prima menzione storica nel 1124 come feudo normanno. Il Museo della Civiltà Contadina e Artigiana conserva circa 1.000 manufatti degli antichi mestieri, una delle collezioni più complete dell\'Irpinia.',
        'population'       => 1638,
        'altitude_meters'  => 850,
        'area_km2'         => 43.6,
        'highlights'       => [
            'Museo della Civiltà Contadina e Artigiana',
            'Siti paleolitici di Pero Spaccone',
        ],
        'notable_products' => [
            'Guanciale del Formicoso PAT',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'aquilonia' => [
        'description' => 'Piccolo borgo irpino a 750 metri di altitudine, custode di due straordinari tesori: il Parco Archeologico di Carbonara, la "Pompei medievale dell\'Irpinia" con l\'impianto urbanistico dell\'antico paese distrutto nel 1930, e il Museo Etnografico "Beniamino Tartaglia", tra i più grandi d\'Europa con oltre 13.000 oggetti originali in 130 ambienti tematici.',
        'population'       => 1617,
        'altitude_meters'  => 750,
        'area_km2'         => 56.15,
        'highlights'       => [
            'Parco Archeologico di Carbonara ("Pompei medievale dell\'Irpinia")',
            'Museo Etnografico "Beniamino Tartaglia" (13.000 oggetti, 130 ambienti)',
        ],
        'notable_products' => [
            'Corresce re cocozza janca (pasta di zucca PAT)',
            'Caciocavallo Irpino di Grotta PAT',
            'Grano antico Senatore Cappelli',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'bisaccia' => [
        'description' => 'Borgo medievale dell\'Alta Irpinia dominato dal Castello Ducale, tra i siti fortificati meglio conservati del Sud Italia con stratificazione longobarda, federiciana e aragonese. Il Museo Civico Archeologico custodisce la Tomba della Principessa (VII sec. a.C.) con 800 reperti della civiltà Oliveto-Cairano.',
        'population'       => 3519,
        'altitude_meters'  => 731,
        'area_km2'         => 37.19,
        'highlights'       => [
            'Castello Ducale',
            'Museo Civico Archeologico (Tomba della Principessa, VII sec. a.C.)',
            'Altopiano del Formicoso',
        ],
        'notable_products' => [
            'Olio Extravergine Irpinia-Colline dell\'Ufita DOP (varietà Ravece)',
            'Caciocavallo Silano DOP',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'cairano' => [
        'description' => 'Piccolo borgo a 770 metri sul livello del mare, soprannominato "il balcone di Dio" dell\'Irpinia. Sede della cultura Oliveto-Cairano (IX-VI sec. a.C.), le necropoli a fossa sono tra le più antiche della Campania. Ospita il Cairano 7X, festival internazionale d\'arte con direzione artistica di Franco Dragone.',
        'population'       => 291,
        'altitude_meters'  => 770,
        'area_km2'         => 14.56,
        'highlights'       => [
            'Il Castello e la Rupe',
            'Necropoli della cultura Oliveto-Cairano (IX-VI sec. a.C.)',
            'Cairano 7X — festival internazionale d\'arte (dir. Franco Dragone)',
            'Fabbrica del Vino — vinificazione con tecniche arcaiche',
        ],
        'notable_products' => [
            'Vino artigianale tradizionale',
        ],
        'notable_experiences' => [
            'Cairano 7X — festival d\'arte e visionarietà',
        ],
        'notable_restaurants' => [],
    ],

    'calitri' => [
        'description' => 'Soprannominata "Positano d\'Irpinia" dal Touring Club Italiano, Calitri è riconosciuta Città Italiana della Ceramica con una tradizione millenaria certificata dal MISE nel 2019. Il centro storico medievale a forma triangolare ospita il Museo della Ceramica, mentre il Sponz Fest — festival internazionale ideato da Vinicio Capossela — ne anima le estati.',
        'population'       => 4127,
        'altitude_meters'  => 465,
        'area_km2'         => 100.88,
        'highlights'       => [
            'Castello medievale del XIII secolo (Borgo Castello)',
            'Museo della Ceramica',
            'Centro storico medievale a gradoni',
        ],
        'notable_products' => [
            'Ceramiche artistiche (Città Italiana della Ceramica)',
            'Cannazze PAT',
            'Pane di Calitri PAT',
            'Caciocavallo Irpino di Grotta PAT',
            'Guanciale del Formicoso PAT',
        ],
        'notable_experiences' => [
            'Sponz Fest — festival internazionale (Vinicio Capossela)',
        ],
        'notable_restaurants' => [],
    ],

    'conza-della-campania' => [
        'description' => 'Borgo dell\'Alta Irpinia che custodisce il Parco Archeologico di Compsa, con oltre 2.000 anni di stratificazione storica: Foro Romano con iscrizioni in bronzo, Anfiteatro, Terme Imperiali e complesso templare. L\'Oasi WWF Lago di Conza (800 ettari) completa un\'offerta naturale e storica di eccezionale valore.',
        'population'       => 1289,
        'altitude_meters'  => 440,
        'area_km2'         => 59.93,
        'highlights'       => [
            'Parco Archeologico di Compsa (Foro Romano, Anfiteatro, Terme Imperiali)',
            'Oasi WWF Lago di Conza (800 ettari)',
            'Co-Cattedrale Santa Maria Assunta',
        ],
        'notable_products' => [
            'Caciocavallo Irpino di Grotta PAT',
            'Miele dell\'Alta Irpinia',
        ],
        'notable_experiences' => [
            'Assedio di Compsa — rievocazione storica in costume d\'epoca',
            'Sagra del Migliariello, Baccalà e Porchetta (25 luglio)',
        ],
        'notable_restaurants' => [],
    ],

    'guardia-dei-lombardi' => [
        'description' => 'A 998 metri di altitudine, tra le vette più alte della Campania, Guardia Lombardi conserva il borgo longobardo fondato nell\'850 d.C. intorno all\'area Giaggia. Il Pecorino di Carmasciano, prodotto in prossimità della Valle d\'Ansanto (Mefite), è considerato una delle rarità casearie d\'Italia per il suo caratteristico aroma vulcanico.',
        'population'       => 1634,
        'altitude_meters'  => 998,
        'area_km2'         => 55.87,
        'highlights'       => [
            'Museo delle Tecnologie, della Cultura e della Civiltà Contadina dell\'Alta Irpinia',
            'Chiesa di Santa Maria delle Grazie (1315, organo 1707)',
            'Chiesa del Miracolo (1600)',
        ],
        'notable_products' => [
            'Pecorino di Carmasciano PAT',
            'Castagne del Monte Cerreto',
        ],
        'notable_experiences' => [
            'Festa patronale di San Leone IX (19 aprile e 6-7 agosto)',
            'Falò di San Giuseppe (19 marzo)',
        ],
        'notable_restaurants' => [],
    ],

    'lacedonia' => [
        'description' => 'Borgo irpino a 732 metri di altitudine, celebre per la Transumanza Patrimonio UNESCO dal 2019. Il MAVI (Museo Antropologico Visuale) custodisce 1.801 fotografie dell\'antropologo Frank Cancian che documentano la cultura rurale lacedoniese degli anni \'50, un archivio fotografico unico al mondo.',
        'population'       => 2022,
        'altitude_meters'  => 732,
        'area_km2'         => 91.05,
        'highlights'       => [
            'MAVI — Museo Antropologico Visuale (1.801 fotografie Frank Cancian)',
            'Transumanza — Patrimonio UNESCO dal 2019',
        ],
        'notable_products' => [
            'Asparagi selvatici di Contrada Forna',
            'Dolci natalizi tradizionali',
            'Caciocavallo dell\'Alta Irpinia',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'lioni' => [
        'description' => 'Comune dell\'Alta Irpinia raso al suolo dal terremoto del 23 novembre 1980 e interamente ricostruito. Oggi Lioni è il primo laboratorio italiano di guida autonoma con il progetto Borgo 4.0 (73 milioni di euro), coniugando la memoria della tragedia con l\'innovazione tecnologica.',
        'population'       => 5915,
        'altitude_meters'  => 550,
        'area_km2'         => 46.17,
        'highlights'       => [
            'Progetto Borgo 4.0 — primo laboratorio italiano di guida autonoma',
            'Ruderi del Castrum longobardo',
            'Castello di Oppido',
        ],
        'notable_products' => [
            'Prodotti da forno Vicenzi (eccellenza locale)',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'monteverde' => [
        'description' => 'Riconosciuto borgo più accessibile d\'Europa nel 2019 dalla Commissione UE, Monteverde coniuga un patrimonio medievale millenario con un\'innovazione per l\'accessibilità che ne fa modello europeo. È l\'unico sito in Campania con nidificazione di cicogna nera.',
        'population'       => 684,
        'altitude_meters'  => 740,
        'area_km2'         => 39.23,
        'highlights'       => [
            'Centro storico medievale (borgo più accessibile d\'Europa 2019 - UE)',
            'Nidificazione di cicogna nera (unico sito in Campania)',
        ],
        'notable_products' => [],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'morra-de-sanctis' => [
        'description' => 'Borgo natale di Francesco De Sanctis (1817-1883), critico letterario e Ministro dell\'Istruzione del Regno d\'Italia. Il Castello dei Principi Biondi-Morra, la Casa natale di De Sanctis e il Parco Letterario che collega 8 comuni irpini compongono un itinerario culturale di rilievo nazionale. A 863 metri di altitudine, con 55 sorgenti naturali e vista sulla valle dell\'Ofanto.',
        'population'       => 1197,
        'altitude_meters'  => 863,
        'area_km2'         => 30.41,
        'highlights'       => [
            'Castello Principi Biondi-Morra',
            'Casa natale di Francesco De Sanctis',
            'Museo di Memorie Desanctisiane',
            'Museo Civico Antiquarium',
            'Parco Letterario Francesco De Sanctis (8 comuni)',
        ],
        'notable_products' => [
            'Caciocavallo del Formicoso PAT',
            'Olio extravergine d\'oliva',
            'Baccalà alla ualanegna (specialità tradizionale di origine normanna)',
        ],
        'notable_experiences' => [
            'Festa del Baccalà (agosto) — Festa dell\'Emigrante',
            'Calici sotto le Stelle al castello',
        ],
        'notable_restaurants' => [],
    ],

    'nusco' => [
        'description' => 'Definito "il Balcone dell\'Irpinia" per la sua posizione panoramica a 914 metri, Nusco custodisce un centro storico medievale integro con la Concattedrale di Sant\'Amato e la sua cripta romanica del XIII secolo. È il primo borgo della provincia di Avellino con fibra ottica ultra-veloce.',
        'population'       => 3791,
        'altitude_meters'  => 914,
        'area_km2'         => 53.61,
        'highlights'       => [
            'Concattedrale di Sant\'Amato (cripta romanica XIII sec.)',
            'Abbazia di Santa Maria di Fontigliano (XII sec.)',
            'Museo Diocesano',
            'Parco Regionale Monti Picentini — Montagnone di Nusco (1.490 m)',
        ],
        'notable_products' => [
            'Castagna di Montella IGP',
            'Caciocavallo podolico',
            'Lardiata PAT',
            'Maccarunari (pasta tradizionale)',
        ],
        'notable_experiences' => [
            'Notte dei Falò (gennaio) — tradizione dal 1656',
            '16+ sentieri CAI nel Parco Regionale Monti Picentini',
        ],
        'notable_restaurants' => [],
    ],

    'rocca-san-felice' => [
        'description' => 'Borgo medievale dell\'Alta Irpinia noto per la Valle d\'Ansanto con la Mefite, sorgente di gas sulfurei venerata come santuario italico della dea Mefite dal VII sec. a.C. (citata da Virgilio nell\'Eneide). Produce il rinomato Pecorino di Carmasciano, uno dei formaggi più rari e pregiati d\'Italia.',
        'population'       => 850,
        'altitude_meters'  => 750,
        'area_km2'         => 14.41,
        'highlights'       => [
            'Valle d\'Ansanto — Mefite (santuario italico, VII sec. a.C.)',
            'Museo Civico "Don Nicola Gambino" (150+ manufatti medievali)',
            'Castello medievale',
        ],
        'notable_products' => [
            'Pecorino di Carmasciano PAT',
            'Agnello di Carmasciano PAT',
            'Ricotta di Carmasciano PAT',
            'Caciocavallo Silano DOP',
        ],
        'notable_experiences' => [
            'Medioevo a la Rocca — rievocazione storica (XXV edizione)',
            'Sentieri escursionistici nel Parco Regionale Monti Picentini',
        ],
        'notable_restaurants' => [],
    ],

    'sant-angelo-dei-lombardi' => [
        'description' => 'Centro storico e culturale dell\'Alta Irpinia, sede dell\'Abbazia del Goleto fondata nel 1133 da San Guglielmo da Vercelli, definita "l\'Assisi del Sud". Il Castello Longobardo del X secolo, la Cattedrale e il Museo Diocesano con il Crocifisso ligneo del XVI secolo compongono un patrimonio di eccezionale valore.',
        'population'       => 3777,
        'altitude_meters'  => 870,
        'area_km2'         => 102.98,
        'highlights'       => [
            'Abbazia del Goleto (fondata 1133, "l\'Assisi del Sud")',
            'Castello Longobardo (X sec.)',
            'Cattedrale di Sant\'Angelo',
            'Museo Diocesano (Crocifisso ligneo XVI sec.)',
        ],
        'notable_products' => [
            'Caciocavallo Irpino di Grotta PAT',
            'Prodotti agroalimentari tradizionali dell\'Alta Irpinia',
        ],
        'notable_experiences' => [
            'Sagra delle Sagre — evento enogastronomico interregionale',
            'Visite guidate all\'Abbazia del Goleto',
        ],
        'notable_restaurants' => [],
    ],

    'sant-andrea-di-conza' => [
        'description' => 'Piccolo comune a 665 metri di altitudine, fondato nel VII secolo da coloni bulgari e un tempo sede vescovile. Il borgo conserva l\'Arco della Terra (antica porta d\'accesso), la fontana monumentale di Piazza Umberto I e i resti del convento francescano di Santa Maria della Consolazione.',
        'population'       => 1402,
        'altitude_meters'  => 665,
        'area_km2'         => 7.05,
        'highlights'       => [
            'Arco della Terra (antica porta d\'accesso al borgo)',
            'Fontana monumentale di Piazza Umberto I',
            'Chiesa di Santa Maria della Consolazione (ex convento francescano)',
        ],
        'notable_products' => [
            'Struffoli Santandreani (dolce tradizionale)',
            'Calzoncelli alle castagne',
            'Caciocavallo Silano DOP',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'torella-dei-lombardi' => [
        'description' => 'Borgo dall\'identità storica e cinematografica unica, noto per il Castello Ruspoli-Candriano medievale perfettamente restaurato e per il legame con il regista Sergio Leone, che lo elesse come location dei suoi western. Il festival cinematografico e il Museo Sergio Leone nel castello lo rendono una meta culturale distintiva dell\'Alta Irpinia.',
        'population'       => 1956,
        'altitude_meters'  => 666,
        'area_km2'         => 26.57,
        'highlights'       => [
            'Castello Ruspoli-Candriano (medievale, perfettamente restaurato)',
            'Museo Sergio Leone nel castello',
            'Chiesa di Santa Maria del Popolo',
        ],
        'notable_products' => [
            'Olio extravergine Irpinia Colline dell\'Ufita DOP',
            'Caciocavallo Silano DOP',
            'Caciocavallo Irpino di Grotta PAT',
        ],
        'notable_experiences' => [
            'Festival del Cinema Sergio Leone',
        ],
        'notable_restaurants' => [],
    ],
];

$updated = 0;
$errors  = [];

foreach ($boroughs as $slug => $data) {
    try {
        // Find borough by slug
        $stmt = $db->prepare("SELECT id FROM boroughs WHERE slug = ? OR id = ?");
        $stmt->execute([$slug, $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            $errors[] = "Borough not found: $slug";
            echo "SKIP: $slug (not found in DB)\n";
            continue;
        }

        $id = $row['id'];

        // Update main fields (only non-zero values)
        $sets  = [];
        $vals  = [];

        $sets[] = 'description = ?'; $vals[] = $data['description'];

        if (!empty($data['population'])) {
            $sets[] = 'population = ?'; $vals[] = (int)$data['population'];
        }
        if (!empty($data['altitude_meters'])) {
            $sets[] = 'altitude_meters = ?'; $vals[] = (int)$data['altitude_meters'];
        }
        if (!empty($data['area_km2'])) {
            $sets[] = 'area_km2 = ?'; $vals[] = (float)$data['area_km2'];
        }

        $vals[] = $id;
        $db->prepare("UPDATE boroughs SET " . implode(', ', $sets) . " WHERE id = ?")
           ->execute($vals);

        // Replace array fields
        replaceArray($db, 'borough_highlights',          'borough_id', $id, $data['highlights']);
        replaceArray($db, 'borough_notable_products',    'borough_id', $id, $data['notable_products']);
        replaceArray($db, 'borough_notable_experiences', 'borough_id', $id, $data['notable_experiences']);
        replaceArray($db, 'borough_notable_restaurants', 'borough_id', $id, $data['notable_restaurants']);

        echo "OK: $slug (id=$id)\n";
        $updated++;
    } catch (Throwable $e) {
        $errors[] = "$slug: " . $e->getMessage();
        echo "ERROR: $slug — " . $e->getMessage() . "\n";
    }
}

echo "\n--- SUMMARY ---\n";
echo "Updated: $updated / " . count($boroughs) . "\n";
if ($errors) {
    echo "Errors:\n";
    foreach ($errors as $e) echo "  - $e\n";
}

// Regenerate static JS
echo "\nRegenerating boroughs JS asset...\n";
try {
    $result = generateBoroughs($db);
    echo "JS regenerated: $result\n";
} catch (Throwable $e) {
    echo "ERROR regenerating JS: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
