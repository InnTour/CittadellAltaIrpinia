<?php
/**
 * Populate borough data extracted from scraped sources.
 * Includes all 25 comuni of Alta Irpinia with enriched data.
 * Run once: GET /api/admin/populate-borghi-from-scraped.php?token=API_TOKEN
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
            'Chiesa di Santa Maria Assunta',
            'Monte Airola',
            'Santuario della Stella Mattutina',
        ],
        'notable_products' => [
            'Guanciale del Formicoso PAT',
            'Cinguli',
            'Caciocavallo Irpino',
        ],
        'notable_experiences' => [
            'Festa della Madonna Stella del Mattino',
            'Festa Patronale di Sant\'Antonio',
        ],
        'notable_restaurants' => [],
    ],

    'aquilonia' => [
        'description' => 'Piccolo borgo irpino a 750 metri di altitudine, custode di due straordinari tesori: il Parco Archeologico di Carbonara, la "Pompei medievale dell\'Irpinia" con l\'impianto urbanistico dell\'antico paese distrutto nel 1930, e il Museo Etnografico "Beniamino Tartaglia", tra i più grandi d\'Europa con oltre 13.000 oggetti originali in 130 ambienti tematici.',
        'population'       => 1617,
        'altitude_meters'  => 750,
        'area_km2'         => 56.15,
        'highlights'       => [
            'Museo Etnografico "Beniamino Tartaglia"',
            'Parco Archeologico di Carbonara',
            'Badia di San Vito',
            'Lago San Pietro',
        ],
        'notable_products' => [
            'Corresce re cocozza janca PAT',
            'Caciocavallo Irpino di Grotta PAT',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'bagnoli-irpino' => [
        'description' => 'Incastonato nel cuore dei Monti Picentini, Bagnoli Irpino è famoso per il Lago Laceno, meta estiva e stazione sciistica, e per il pregiato tartufo nero che cresce nei boschi circostanti.',
        'population'       => 2900,
        'altitude_meters'  => 654,
        'area_km2'         => 67.2,
        'highlights'       => [
            'Lago Laceno',
            'Stazione sciistica dei Monti Picentini',
            'Boschi da tartufo nero',
        ],
        'notable_products' => [
            'Tartufo nero di Bagnoli',
            'Pecorino di Bagnoli',
            'Castagne dei Picentini',
        ],
        'notable_experiences' => [
            'Trekking nei Monti Picentini',
            'Sagra del Tartufo nero',
            'Sci alpino al Laceno',
        ],
        'notable_restaurants' => [],
    ],

    'bisaccia' => [
        'description' => 'Borgo medievale dell\'Alta Irpinia dominato dal Castello Ducale, tra i siti fortificati meglio conservati del Sud Italia con stratificazione longobarda, federiciana e aragonese. Il Museo Civico Archeologico custodisce la Tomba della Principessa (VII sec. a.C.) con 800 reperti della civiltà Oliveto-Cairano.',
        'population'       => 3519,
        'altitude_meters'  => 731,
        'area_km2'         => 37.19,
        'highlights'       => [
            'Castello Ducale',
            'Museo Civico Archeologico',
            'Altopiano del Formicoso',
        ],
        'notable_products' => [
            'Guanciale del Formicoso PAT',
            'Oliva Masciatica PAT',
            'Caciocavallo Irpino di Grotta PAT',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'calabritto' => [
        'description' => 'Calabritto sorge alle pendici dei Monti Picentini, nella verde valle del Sele. Il territorio, avvolto da faggi e castagneti secolari, ospita l\'Oasi WWF della Valle della Caccia, rifugio di biodiversità e meta per escursionisti.',
        'population'       => 2200,
        'altitude_meters'  => 550,
        'area_km2'         => 51.6,
        'highlights'       => [
            'Oasi WWF Valle della Caccia',
            'Cascate dei Picentini',
            'Boschi di faggio e castagno',
        ],
        'notable_products' => [
            'Castagne di Calabritto',
            'Miele di castagno',
        ],
        'notable_experiences' => [
            'Visita all\'Oasi WWF',
            'Trekking alle cascate',
        ],
        'notable_restaurants' => [],
    ],

    'cairano' => [
        'description' => 'Piccolo borgo a 770 metri sul livello del mare, soprannominato "il balcone di Dio" dell\'Irpinia. Sede della cultura Oliveto-Cairano (IX-VI sec. a.C.), le necropoli a fossa sono tra le più antiche della Campania. Ospita il Cairano 7X, festival internazionale d\'arte con direzione artistica di Franco Dragone.',
        'population'       => 291,
        'altitude_meters'  => 770,
        'area_km2'         => 14.56,
        'highlights'       => [
            'La Rupe e l\'organo a vento',
            'Cantine ipogee',
            'Museo delle Relazioni Felicitanti',
            'Sito Archeologico Collina del Calvario',
        ],
        'notable_products' => [
            'Cinguli di San Martino',
            'Vini artigianali',
        ],
        'notable_experiences' => [
            'Cairano 7X — festival internazionale d\'arte',
            'SponzFest',
            'Fabbrica del Vino',
        ],
        'notable_restaurants' => [],
    ],

    'calitri' => [
        'description' => 'Soprannominata "Positano d\'Irpinia" dal Touring Club Italiano, Calitri è riconosciuta Città Italiana della Ceramica con una tradizione millenaria certificata dal MISE nel 2019. Il centro storico medievale a forma triangolare ospita il Museo della Ceramica, mentre il Sponz Fest — festival internazionale ideato da Vinicio Capossela — ne anima le estati.',
        'population'       => 4127,
        'altitude_meters'  => 465,
        'area_km2'         => 100.88,
        'highlights'       => [
            'Borgo Castello',
            'Museo della Ceramica',
            'Torre della Porta di Nanno',
        ],
        'notable_products' => [
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

    'caposele' => [
        'description' => 'Caposele custodisce la sorgente del fiume Sele, le cui acque purissime alimentano il grande Acquedotto Pugliese. Il Santuario di San Gerardo Maiella, meta di pellegrinaggio regionale, arricchisce la vocazione spirituale e naturalistica del borgo.',
        'population'       => 3300,
        'altitude_meters'  => 503,
        'area_km2'         => 41.2,
        'highlights'       => [
            'Sorgenti del Sele',
            'Acquedotto Pugliese',
            'Santuario di San Gerardo Maiella',
        ],
        'notable_products' => [
            'Caciocavallo Irpino',
            'Olio extravergine d\'oliva',
        ],
        'notable_experiences' => [
            'Tour alle sorgenti del Sele',
            'Pellegrinaggio al Santuario di San Gerardo',
        ],
        'notable_restaurants' => [],
    ],

    'cassano-irpino' => [
        'description' => 'Piccolo borgo immerso tra i castagneti ai piedi del Monte Terminio, Cassano Irpino offre una quiete autentica e sentieri naturalistici che conducono tra boschi millenari, in un paesaggio montano intatto.',
        'population'       => 950,
        'altitude_meters'  => 594,
        'area_km2'         => 14.4,
        'highlights'       => [
            'Boschi di castagno del Terminio',
            'Monte Terminio',
            'Sentieri naturalistici',
        ],
        'notable_products' => [
            'Castagne del Terminio',
            'Nocciole di montagna',
        ],
        'notable_experiences' => [
            'Raccolta delle castagne',
            'Trekking sul Monte Terminio',
        ],
        'notable_restaurants' => [],
    ],

    'castelfranci' => [
        'description' => 'Castelfranci è cuore pulsante della viticoltura irpina, con terreni vulcanici che conferiscono ai suoi vini un carattere minerale distintivo. Tra vigneti storici e cantine tradizionali, il borgo celebra la grande tradizione enologica dell\'Irpinia.',
        'population'       => 1900,
        'altitude_meters'  => 460,
        'area_km2'         => 14.9,
        'highlights'       => [
            'Vigneti storici',
            'Cantine tradizionali',
            'Paesaggi viticoli dell\'Irpinia',
        ],
        'notable_products' => [
            'Fiano di Avellino DOCG',
            'Aglianico del Taurasi',
            'Greco di Tufo DOCG',
        ],
        'notable_experiences' => [
            'Degustazione in cantina',
            'Tour tra i vigneti',
        ],
        'notable_restaurants' => [],
    ],

    'conza-della-campania' => [
        'description' => 'Borgo dell\'Alta Irpinia che custodisce il Parco Archeologico di Compsa, con oltre 2.000 anni di stratificazione storica: Foro Romano con iscrizioni in bronzo, Anfiteatro, Terme Imperiali e complesso templare. L\'Oasi WWF Lago di Conza (800 ettari) completa un\'offerta naturale e storica di eccezionale valore.',
        'population'       => 1289,
        'altitude_meters'  => 440,
        'area_km2'         => 59.93,
        'highlights'       => [
            'Parco Archeologico di Compsa',
            'Oasi WWF Lago di Conza',
            'Concattedrale di Santa Maria Assunta',
        ],
        'notable_products' => [
            'Caciocavallo Irpino di Grotta PAT',
        ],
        'notable_experiences' => [
            'L\'Assedio di Compsa — rievocazione storica',
            'Sagra del Migliariello',
        ],
        'notable_restaurants' => [],
    ],

    'guardia-dei-lombardi' => [
        'description' => 'A 998 metri di altitudine, tra le vette più alte della Campania, Guardia Lombardi conserva il borgo longobardo fondato nell\'850 d.C. intorno all\'area Giaggia. Il Pecorino di Carmasciano, prodotto in prossimità della Valle d\'Ansanto (Mefite), è considerato una delle rarità casearie d\'Italia per il suo caratteristico aroma vulcanico.',
        'population'       => 1634,
        'altitude_meters'  => 998,
        'area_km2'         => 55.87,
        'highlights'       => [
            'Chiesa di Santa Maria delle Grazie',
            'Museo delle Tecnologie e della Civiltà Contadina',
        ],
        'notable_products' => [
            'Agnello di Carmasciano PAT',
            'Pecorino di Carmasciano PAT',
            'Ricotta di Carmasciano PAT',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'lacedonia' => [
        'description' => 'Borgo irpino a 732 metri di altitudine, celebre per la Transumanza Patrimonio UNESCO dal 2019. Il MAVI (Museo Antropologico Visuale) custodisce 1.801 fotografie dell\'antropologo Frank Cancian che documentano la cultura rurale lacedoniese degli anni \'50, un archivio fotografico unico al mondo.',
        'population'       => 2022,
        'altitude_meters'  => 732,
        'area_km2'         => 91.05,
        'highlights'       => [
            'Castello di Pappacota',
            'MAVI — Museo Antropologico Visuale',
            'Bosco di Origlio',
            'Concattedrale di Santa Maria Assunta',
        ],
        'notable_products' => [
            'Caciocavallo Irpino di Grotta PAT',
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
            'Museo Etnografico Antropologico',
            'Castello di Oppido',
            'Castrum longobardo',
        ],
        'notable_products' => [
            'Treccia lionese',
            'Torrone millefiori',
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
            'Castello Baronale Grimaldi',
            'Museo M.I.GRA',
            'Lago San Pietro',
            'Serro della Croce',
        ],
        'notable_products' => [
            'Birra Serrocroce',
            'Caciocavallo Silano DOP',
        ],
        'notable_experiences' => [],
        'notable_restaurants' => [],
    ],

    'montella' => [
        'description' => 'Montella è la capitale della Castagna IGP, la più pregiata d\'Italia, coltivata nei castagneti pluricentenari dei Picentini. Il Santuario del Santissimo Salvatore sul monte e la tradizione della raccolta autunnale ne fanno una destinazione di eccellenza enogastronomica e spirituale.',
        'population'       => 7500,
        'altitude_meters'  => 600,
        'area_km2'         => 57.6,
        'highlights'       => [
            'Castagneti pluricentenari dei Picentini',
            'Santuario del Santissimo Salvatore',
            'Centro storico medievale',
        ],
        'notable_products' => [
            'Castagna di Montella IGP',
            'Miele di castagno dei Picentini',
            'Nocciole',
        ],
        'notable_experiences' => [
            'Raccolta delle castagne (ottobre)',
            'Sagra della Castagna',
            'Trekking al Santuario del Salvatore',
        ],
        'notable_restaurants' => [],
    ],

    'morra-de-sanctis' => [
        'description' => 'Borgo natale di Francesco De Sanctis (1817-1883), critico letterario e Ministro dell\'Istruzione del Regno d\'Italia. Il Castello dei Principi Biondi-Morra, la Casa natale di De Sanctis e il Parco Letterario che collega 8 comuni irpini compongono un itinerario culturale di rilievo nazionale. A 863 metri di altitudine, con 55 sorgenti naturali e vista sulla valle dell\'Ofanto.',
        'population'       => 1197,
        'altitude_meters'  => 863,
        'area_km2'         => 30.41,
        'highlights'       => [
            'Castello dei Principi Biondi-Morra',
            'Museo Civico Antiquarium',
            'Palazzo De Sanctis',
            'Museo di Memorie Desanctisiane',
        ],
        'notable_products' => [
            'Baccalà alla Ualanegna',
            'Ricotta di Carmasciano PAT',
        ],
        'notable_experiences' => [
            'Sagra del Baccalà',
            'Calici sotto le Stelle',
        ],
        'notable_restaurants' => [],
    ],

    'nusco' => [
        'description' => 'Definito "il Balcone dell\'Irpinia" per la sua posizione panoramica a 914 metri, Nusco custodisce un centro storico medievale integro con la Concattedrale di Sant\'Amato e la sua cripta romanica del XIII secolo. È il primo borgo della provincia di Avellino con fibra ottica ultra-veloce.',
        'population'       => 3791,
        'altitude_meters'  => 914,
        'area_km2'         => 53.61,
        'highlights'       => [
            'Concattedrale di Sant\'Amato',
            'Museo Diocesano',
            'Abbazia di Santa Maria di Fontigliano',
        ],
        'notable_products' => [
            'Cicaluccoli',
            'Caciocavallo podolico dei Monti Picentini PAT',
        ],
        'notable_experiences' => [
            'Notte dei Falò — tradizione dal 1656',
        ],
        'notable_restaurants' => [],
    ],

    'rocca-san-felice' => [
        'description' => 'Borgo medievale dell\'Alta Irpinia noto per la Valle d\'Ansanto con la Mefite, sorgente di gas sulfurei venerata come santuario italico della dea Mefite dal VII sec. a.C. (citata da Virgilio nell\'Eneide). Produce il rinomato Pecorino di Carmasciano, uno dei formaggi più rari e pregiati d\'Italia.',
        'population'       => 850,
        'altitude_meters'  => 750,
        'area_km2'         => 14.41,
        'highlights'       => [
            'Geosito Mefite — Valle d\'Ansanto',
            'Castello di Rocca San Felice',
            'Museo Civico Don Nicola Gambino',
        ],
        'notable_products' => [
            'Pecorino di Carmasciano PAT',
            'Agnello di Carmasciano PAT',
        ],
        'notable_experiences' => [
            'Festa Medievale',
            'Festival dei Cortili',
        ],
        'notable_restaurants' => [],
    ],

    'sant-angelo-dei-lombardi' => [
        'description' => 'Centro storico e culturale dell\'Alta Irpinia, sede dell\'Abbazia del Goleto fondata nel 1133 da San Guglielmo da Vercelli, definita "l\'Assisi del Sud". Il Castello Longobardo del X secolo, la Cattedrale e il Museo Diocesano con il Crocifisso ligneo del XVI secolo compongono un patrimonio di eccezionale valore.',
        'population'       => 3777,
        'altitude_meters'  => 870,
        'area_km2'         => 102.98,
        'highlights'       => [
            'Abbazia del Goleto',
            'Cattedrale di Sant\'Antonino',
            'Castello degli Imperiale',
        ],
        'notable_products' => [
            'Caciocavallo Irpino di Grotta PAT',
        ],
        'notable_experiences' => [
            'Sagra delle Sagre',
            'Cammino di San Guglielmo',
        ],
        'notable_restaurants' => [],
    ],

    'sant-andrea-di-conza' => [
        'description' => 'Piccolo comune a 665 metri di altitudine, fondato nel VII secolo da coloni bulgari e un tempo sede vescovile. Il borgo conserva l\'Arco della Terra (antica porta d\'accesso), la fontana monumentale di Piazza Umberto I e i resti del convento francescano di Santa Maria della Consolazione.',
        'population'       => 1402,
        'altitude_meters'  => 665,
        'area_km2'         => 7.05,
        'highlights'       => [
            'Episcopio',
            'Arco della Terra',
            'Chiesa di San Domenico',
            'Seminario Vescovile',
        ],
        'notable_products' => [
            'Struffoli Santandreani',
            'Calzoncelli di castagne',
        ],
        'notable_experiences' => [
            'Processione dei Misteri',
            'Rito delle Maggiaiole',
        ],
        'notable_restaurants' => [],
    ],

    'senerchia' => [
        'description' => 'Senerchia ospita l\'Oasi WWF "Valle della Caccia", scrigno di natura incontaminata con cascate, faggete e sentieri immersi in una biodiversità rara. Un paradiso per gli escursionisti e gli amanti della natura selvaggia.',
        'population'       => 700,
        'altitude_meters'  => 540,
        'area_km2'         => 31.2,
        'highlights'       => [
            'Oasi WWF Valle della Caccia',
            'Cascate di Senerchia',
            'Faggete e sentieri naturalistici',
        ],
        'notable_products' => [
            'Miele biologico di montagna',
            'Erbe spontanee officinali',
        ],
        'notable_experiences' => [
            'Escursioni nell\'Oasi WWF',
            'Trekking alle cascate',
            'Workshop di erboristeria',
        ],
        'notable_restaurants' => [],
    ],

    'teora' => [
        'description' => 'Teora è il borgo della musica e del carnevale irpino. Dopo la devastazione del terremoto del 1980, la comunità ha saputo rinascere custodendo le proprie tradizioni più autentiche, dalla musica popolare ai riti del carnevale tra i più vivaci dell\'Alta Irpinia.',
        'population'       => 1400,
        'altitude_meters'  => 660,
        'area_km2'         => 23.0,
        'highlights'       => [
            'Carnevale Teorese',
            'Tradizione musicale popolare',
            'Museo della Memoria post-sisma',
        ],
        'notable_products' => [
            'Prodotti da forno tradizionali',
            'Conserve artigianali',
        ],
        'notable_experiences' => [
            'Carnevale Teorese',
            'Festival della musica popolare',
        ],
        'notable_restaurants' => [],
    ],

    'torella-dei-lombardi' => [
        'description' => 'Borgo dall\'identità storica e cinematografica unica, noto per il Castello Ruspoli-Candriano medievale perfettamente restaurato e per il legame con il regista Sergio Leone, che lo elesse come location dei suoi western. Il festival cinematografico e il Museo Sergio Leone nel castello lo rendono una meta culturale distintiva dell\'Alta Irpinia.',
        'population'       => 1956,
        'altitude_meters'  => 666,
        'area_km2'         => 26.57,
        'highlights'       => [
            'Castello Ruspoli-Candriano',
            'Torre Normanna di Girifalco',
            'Museo Civico Turella Parva Turris',
        ],
        'notable_products' => [
            'Olio Irpinia Colline dell\'Ufita DOP',
            'Caciocavallo Silano DOP',
        ],
        'notable_experiences' => [
            'Sapori Antichi',
        ],
        'notable_restaurants' => [],
    ],

    'villamaina' => [
        'description' => 'Villamaina è nota per le terme sulfuree, già frequentate in epoca romana per le proprietà curative delle sue acque. Il paesaggio collinare dolce e le sorgenti termali naturali ne fanno una destinazione di benessere autentico nel cuore dell\'Irpinia.',
        'population'       => 900,
        'altitude_meters'  => 540,
        'area_km2'         => 8.4,
        'highlights'       => [
            'Terme sulfuree di Villamaina',
            'Sorgenti curative naturali',
            'Paesaggio collinare irpino',
        ],
        'notable_products' => [
            'Erbe medicinali locali',
            'Miele di fiori selvatici',
        ],
        'notable_experiences' => [
            'Bagni termali naturali',
            'Percorso benessere alle sorgenti',
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
