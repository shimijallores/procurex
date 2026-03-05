<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => '924 FOOD SERVICES', 'person' => 'MR. ROBIN ROLAND A. TUMAMBING', 'address' => 'Blk. 8, Lot 23 Camella Homes, Alangilan, Batangas City'],
            ['name' => 'EARTHCLEAN ENVIRONMENTAL MANAGEMENT CORP.', 'person' => 'MS. AIDA V. MACATANGAY', 'address' => 'Purok Almasiga, Inicbulan, Bauan, Batangas'],
            ['name' => 'E AUTOTECH CAR CARE 1 OPC', 'person' => 'MS. ROSSEL VILLANUEVA', 'address' => 'Brgy. Soro-Soro, Karsada, Batangas City'],
            ['name' => 'DVD POWER BEAT MOBILE', 'person' => 'MR. CIPRIANO DANTE V. DIMAANDAL', 'address' => 'Kumintang Ibaba, Batangas City'],
            ['name' => 'DIGIT-ALL ENTERPRISES', 'person' => 'MR. ROMULO C. MARASIGAN', 'address' => 'Brgy. 22 Rizal Ave., Batangas City'],
            ['name' => 'DANILO B. BISCO MOTOR WORKS', 'person' => 'MS. LENI MAGADIA', 'address' => 'Kumintang Ibaba, Batangas City'],
            ['name' => 'D.S.E. AUTOBATS CAR CARE CENTER', 'person' => 'MR. DON SANTOS EDUARDO', 'address' => 'J.P. Laurel Highway, Marawoy, Lipa City, Batangas'],
            ['name' => 'D. DIMAANDAL CAR CARE CENTER', 'person' => 'MR. CIPRIANO DANTE V. DIMAANDAL', 'address' => 'Kumintang Ibaba, Batangas City'],
            ['name' => 'CYCLOOPS REF & AUTO AIRCON SERVICES', 'person' => 'MR. NEIL P. OLIVAR', 'address' => 'Tubigan, Lemery, Batangas'],
            ['name' => 'COPYLANDIA OFFICE SYSTEMS CORPORATION', 'person' => 'MR. GENE U. ORPIANO', 'address' => '122 JP Laurel Highway Brgy. Mataas na Lupa, Lipa City'],
            ['name' => 'COPYLANDIA OFFICE SYSTEMS CORPORATION (COPYLANDIA)', 'person' => 'MS. ELVIEN MANGUNDAY', 'address' => '122 JP Laurel Highway Brgy. Mataas na Lupa, Lipa City'],
            ['name' => 'CLOUD READY TECHNOLOGIES CORP.', 'person' => 'MS. JESSICA M. BAJANO', 'address' => 'Unit 707 One Park Drive, Corner 9th Ave., Taguig, Metro Manila'],
            ['name' => 'CHRIS ELECTRICAL REFRIGERATION AND AIRCONDITIONING SERVICES', 'person' => 'MR. CHRISTIAN E. BENTULA', 'address' => 'Brgy. Malaruhatan, Lian, Batangas'],
            ['name' => 'CHRIS ELECTRICAL REFRIGERATION AND AIRCONDITIONING SERVICES', 'person' => 'MR. CHRISTIAN E. BENTULA', 'address' => 'Brgy. Malaruhatan, Lian, Batangas'],
            ['name' => 'CENTURY OFFICE EQUIPMENT TRADING', 'person' => 'MS. ANNALYN M. HUMARANG', 'address' => '2nd Rd. Arce Subd., Kumintang Ibaba, Batangas City'],
            ['name' => 'CDM ENTERPRISE', 'person' => 'MS. CHERYL D. MORATO', 'address' => 'Maharlika Highway, Ibabang Iyam, Lucena City'],
            ['name' => 'CASA ESCONDIDA ANILAO RESORT', 'person' => 'MR. ARMANDO G. VERGARA', 'address' => 'Ligaya, Mabini, Batangas'],
            ['name' => 'CAS REALTY AND DEVELOPMENT CORPORATION', 'person' => 'MR. JAYSON P. ILAGAN', 'address' => '84 Pres. J.P. Laurel Highway, Pilahan, Sabang, Lipa City'],
            ['name' => 'CAMPOSO OFFICE SUPPLIES TRADING', 'person' => 'MR. WENSON L. CAMPOSO', 'address' => 'Sabang Lipa City, Batangas'],
            ['name' => 'BRIXTON CONSTRUCTION & INDUSTRIAL SUPPLY CORP.', 'person' => 'MR. ALVIN CRIS VILLANUEVA', 'address' => 'Sitio 6, Diversion Rd. Brgy. Balagtas, Batangas City'],
            ['name' => 'BLUE SAPPHIRE SOCIAL EVENTS CORP.', 'person' => 'MS. BERNARDINE B. GO', 'address' => 'Purok 5, Sico, Lipa City'],
            ['name' => 'BLISS CONSTRUCTION SUPPLY', 'person' => 'MR. ALBERT TAN', 'address' => 'Poblacion, San Pascual, Batangas'],
            ['name' => 'BIOCARE INC.', 'person' => 'MS. LYNDEMIE C. ARIT', 'address' => '64 WebJet Bldg., Quezon Avenue, Quezon City'],
            ['name' => 'BIOASSETS CORPORATION', 'person' => 'MS. ZYNE K. BAYBAY', 'address' => '2F, NDN Bldg., San Roque, Santo Tomas, Batangas'],
            ['name' => 'BATS SCUBA DIVING SCHOOL', 'person' => 'MS. MARIVIC V. MARAMOT', 'address' => 'Pallocan, Batangas City'],
            ['name' => 'BATANGAS POST', 'person' => 'MR. JAY FERDINAND C. MALALUAN', 'address' => 'De Joya Compound, Alangilan, Batangas City'],
            ['name' => 'AUTO TUNES CAR ACCESSORIES CENTER', 'person' => 'MR. MICHAEL R. ONG', 'address' => 'Kumintang Ilaya, Batangas City'],
            ['name' => 'ASTROPHIL TRADING', 'person' => 'MR. ARVIN D. MAGHIRANG', 'address' => 'San Pablo City, Laguna 4000'],
            ['name' => 'ASHCOLE HOTELS & RESORTS CORP.', 'person' => 'MR. ROBERT LINATOC', 'address' => 'Sabang, Lipa City'],
            ['name' => 'ARKITEKNIK CADD & DIGITAL PRINTS', 'person' => 'MR. TEODORO M. PANOPIO', 'address' => 'Hilltop Road, Kumintang Ibaba, Batangas City'],
            ['name' => 'APOLINAR PRESS & PUBLISHING HOUSE', 'person' => 'MS. ROSARIO E. CAPACIA', 'address' => 'Balayan, Batangas'],
            ['name' => 'ALPHARON BUILDERS AND TRADING', 'person' => 'MR. RONEL O. MARQUEZ', 'address' => 'Alangilan, Batangas City'],
            ['name' => 'ADVANCED RUBBER & SALES CORPORATION', 'person' => 'MS. JOAN GRACE F. MOSTAJO', 'address' => 'Balintawak, Lipa City'],
            ['name' => 'A. MONTALBO PRINTING SERVICES', 'person' => 'MR. ARNEL B. MONTALBO', 'address' => 'Rizal Avenue, Batangas City'],
            ['name' => 'GAUDENCIO KASILAG CATTLE TRADING', 'person' => 'MS. EMELINDA S. KASILAG', 'address' => 'Bawi Padre Garcia, Batangas'],
            ['name' => 'GALIT DIGITAL PRINTING SERVICES', 'person' => 'MR. ARNEL D. GALIT', 'address' => 'A. Mabini St., Poblacion, Lobo, Batangas'],
            ['name' => 'G. BALMES PRINTING PRESS', 'person' => 'MS. GLORIA R. BALMES', 'address' => 'M.H. Del Pilar St., Batangas City'],
            ['name' => 'FYKER ENTERPRISES', 'person' => 'MR. FELICIANO H. EBREO', 'address' => '086 Jasmin St., Dolor Subdivision, Batangas City'],
            ['name' => 'FOREST CREST NATURE HOTEL & RESORT INC.', 'person' => 'MR. MELMAR M. MERCADO', 'address' => 'Km 72 Batulao, Nasugbu, Batangas'],
            ['name' => 'FIRST ST. RITA AIRCONDITIONING SERVICES', 'person' => 'MR. FRANCISCO C. ILAGAN', 'address' => 'Dimacuha Rd., Sta. Rita, Batangas City'],
            ['name' => 'FINESTDEAL TRADING CORP.', 'person' => 'MS. MARIA CONCEPCION P. QUIAMBAO', 'address' => 'Hilltop Road, Kumintang Ibaba, Batangas City'],
            ['name' => 'FILRA TRADING AND GENERAL SERVICES', 'person' => 'MS. KATHERINE JOY M. ROQUE', 'address' => '#40 M.H. Del Pilar St. Batangas City'],
            ['name' => 'FILHOLLAND CORPORATION', 'person' => 'MS. MARICON TORNEA', 'address' => 'Taguig City'],
            ['name' => 'ESTILO DE QUIWA ENTERPRISE', 'person' => 'MR. JUDE ALDANA', 'address' => 'Purok 10 Rose Ave., Sta Lucia, City of San Fernando, Pampanga'],
            ['name' => 'ESSEM GENERATOR ENTERPRISE', 'person' => 'MR. FREDERICK S. SARROL', 'address' => 'Manghinao Proper, Bauan, Batangas'],
            ['name' => 'EQUITY POINT GENERAL MERCHANDISE AND TRADING INC.', 'person' => 'MS. KENNETH CARL G. VELASQUEZ', 'address' => 'Bagongpook, Lipa City'],
            ['name' => 'FILHOLLAND CORPORATION', 'person' => 'MS. MARICON TORNEA', 'address' => 'Taguig City'],
            ['name' => 'PARES DE FESING CATERING SERVICES', 'person' => 'MR. VON DERICK G. EBREO', 'address' => '086 Jasmin St., Dolor Subd., Batangas City'],
            ['name' => 'PAPA PEST CONTROL & GENERAL SERVICES INC.', 'person' => 'MS. MARY JANE M. FRANE', 'address' => 'El Puerto Real Subd., Alangilan, Batangas City'],
            ['name' => 'PAJEAS INFINITY ADS AND PRINTS', 'person' => 'MR. PAULO EDMART B. PEREZ', 'address' => 'Ebora Road, Kumintang Ibaba, Batangas City'],
            ['name' => 'PAHAYAGANG BALIKAS, INC.', 'person' => 'MR. JOENALD RAYOS', 'address' => 'Alangilan, Batangas City'],
            ['name' => 'ONEKLIK EVENTS MANAGEMENT SERVICES', 'person' => 'MR. RAJ JOSHUA T. BANGI', 'address' => '10 Molave St., Proj. 3, Quezon City'],
            ['name' => "NORMA CLEOFE'S CANTEEN", 'person' => 'MS. MARIA KATHERINE CLEOFE', 'address' => 'Kumintang Ibaba, Batangas City'],
            ['name' => 'NATIVE LUMBER', 'person' => 'MS. REMEGIA V. TAN', 'address' => 'Hilltop Road, Kumintang Ibaba, Batangas City'],
            ['name' => 'MICHAEL AIRCON & REFRIGERATION REPAIR SERVICES', 'person' => 'MR. MICHAEL O. MENDOZA', 'address' => 'Brgy. Lumbangan, Nasugbu, Batangas'],
            ['name' => 'MGK AUTOWORKS SERVICE CENTER', 'person' => 'MR. MERVIN E. BALDOZ', 'address' => 'Halang, Taal, Batangas'],
            ['name' => 'METRO LEMERY GENERAL MERCHANDISE AND CONSTRUCTION CORP.', 'person' => 'MS. JOCELYN MENDOZA', 'address' => 'Ilustre Avenue, Lemery Batangas'],
            ['name' => 'MERRY MERCHANTS TRADING & GEN. MDSE.', 'person' => 'MS. FHERRIZA LORRAINE E. DEL MUNDO', 'address' => '#145 National Road, Brgy. Palindan, Ibaan, Batangas'],
            ['name' => 'MEL-C TRADING', 'person' => 'MS. EMILY D. MALALUAN', 'address' => 'Brgy. Pinagkurusan, Alitagtag, Batangas'],
            ['name' => 'MBJ CANTEEN & CATERING SERVICES', 'person' => 'MS. MARITES B. JUMARANG', 'address' => 'Brgy. Poblacion 2, Cuenca, Batangas'],
            ['name' => 'MASANGKAY COMPUTER CENTER', 'person' => 'MS. LOIDA L. EVANGELISTA', 'address' => '1143 G. Masangkay St. Sta Cruz, Manila'],
            ['name' => 'MARY LAURYNE CATERING SERVICES', 'person' => 'MS. AVA JOANNE C. MARQUESES', 'address' => '47 Noble Street, Batangas City'],
            ['name' => 'MAC TYCOON MARKETING', 'person' => 'MS. RUTH A. ARRIOLA', 'address' => '1560 Bambang St.., Sta. Cruz, Manila'],
            ['name' => 'M3C TRADING', 'person' => 'MS. LOIDA R. DOTONG', 'address' => 'Kumintang Ibaba, Batangas City'],
            ['name' => 'M AND A TRADING', 'person' => 'MS. MARIA ROMELYN L. MASANGKAY', 'address' => 'Soro-Soro Ilaya, Batangas City'],
            ['name' => 'LUCKY - BK JANITORIAL OFFICE SUPPLIES TRADING', 'person' => 'MR. BRYAN OLIVER H. GARCIA', 'address' => 'Gulod Itaas, Batangas City'],
            ['name' => 'LUCENA DIGITAL TRADING AND COPY CENTER', 'person' => 'MR. ANDY BANTAYAN', 'address' => 'G/F Miramart Bldg., Quezon Ave Cor. Zamora St., Lucena City'],
            ['name' => "LOLO'S RESTAURANT", 'person' => 'MS. SHIERNA S. BERCES', 'address' => 'Sitio Maligaya, Cuta, Batangas City'],
            ['name' => 'LED PRO SERVICES & ELECTRONIC EQUIPMENT TRADING', 'person' => 'MR. JJ PAULO CORDERO', 'address' => 'San Pascual, Batangas'],
            ['name' => 'L.E. PANOPIO PUMPS WELL DRILLING & CONSTRUCTION', 'person' => 'MR. LANDER R. PANOPIO', 'address' => 'Sta. Rita Kalsada Batangas City'],
            ['name' => 'KEMIKO SCIENTIFIC TRADING CORP.', 'person' => 'MR. JOEL A. PANGANIBAN', 'address' => 'Purok 4, Poblacion, Nabunturan, Davao de Oro'],
            ['name' => 'JUNIKEN ENTERPRISES', 'person' => 'MS. CASSANDRA MAE B. HERNANDEZ', 'address' => 'Zobel St., Pob. I Calatagan, Batangas'],
            ['name' => 'JORDA TRADING', 'person' => 'MS. ROSARIO E. CAPACIA', 'address' => 'Balayan, Batangas'],
            ['name' => 'JOFARENZ PRINTING AND ENTERPRISES', 'person' => 'MR. NELSON L. CHAVEZ', 'address' => '875 Libjo, D\'Hope, Batangas City'],
            ['name' => 'JHULIAN\'S CATERING SERVICES', 'person' => 'MS. JOYCE S. MILLAREZ', 'address' => 'Tanauan City, Batangas'],
            ['name' => 'JETSTER ENTERPRISES', 'person' => 'MR. JAN DEXTER PAMPLONA', 'address' => 'Tanauan City'],
            ['name' => 'JCT CONSTRUCTION SUPPLIES & TRADING', 'person' => 'MR. JOEY C. TEJADA', 'address' => 'Blk. 8, Lot 23 Camella Homes, Alangilan, Batangas City'],
            ['name' => 'JAIME B.M. REFRIGERATION & AIRCONDITIONING', 'person' => 'MR. JAIME B. MAGADIA', 'address' => 'Roxas Rd., Kumintang Ibaba, Batangas City'],
            ['name' => 'ISLAND OF CORALS BEACH RESORT AND CATERING SERVICES', 'person' => 'MS. ELSA C. LONTOC', 'address' => 'Lagadlarin, Lobo, Batangas'],
            ['name' => 'HOTEL CONCEPTS, INC.', 'person' => 'MS. RACHELLE FATIMA M. COMIA', 'address' => 'Gulod Labac, Batangas City'],
            ['name' => 'GREEN THUMB MARKETING', 'person' => 'MS. GLORIA M. MAGLANTAY', 'address' => 'Analyn Subd., Alangilan, Batangas City'],
            ['name' => 'GENSSON TRADE CORPORATION', 'person' => 'MR. ALBERTO UY BOMPING', 'address' => 'DJPMM Access Rd., Santa Clara, Batangas City'],
            ['name' => 'REVTUNE AUTO REPAIR SHOP', 'person' => 'MR. RICARDO PACIA', 'address' => 'Sitio 7 Balagtas, Batangas City'],
            ['name' => 'REGIONEER NEWSWEEKLY', 'person' => 'MS. JAIMELEE D. PEREZ', 'address' => 'Alangilan, Batangas City'],
            ['name' => "QUIZON'S CATERING SERVICES", 'person' => 'MR. CHRIS ANTHONY M. QUIZON', 'address' => 'Talisay, Lipa City, Batangas'],
            ['name' => 'PRINTAGLAM DIGITAL PRINTING SERVICES', 'person' => 'MS. EVANGELINE B. BANTIGUE', 'address' => 'San Isidro, Batangas City'],
            ['name' => 'PRIME STAR TRADING', 'person' => 'MS. MHARA DIANIA D. ASI', 'address' => 'Kumintang Ilaya, Batangas City'],
            ['name' => 'PHILIPPINE EXHIBITIONS AND TRADE CORPORATION', 'person' => 'MS. MARIA CRISTINA NALLANA', 'address' => 'Liberty Bldg., 835 A.Arnaiz Ave.,Legaspi Village, San Lorenzo, Makati City'],
            ['name' => 'RLD AUTOWORX & TOWING SERVICES', 'person' => 'MR. ROLDAN L. DUENAS', 'address' => 'Buli, Taal, Batangas'],
            ['name' => 'RJEN SCHOOL AND OFFICE SUPPLIES TRADING', 'person' => 'MS. JENNIFER M. ABACA', 'address' => 'B. Morada Avenue, Pob. Brgy. I, Lipa City, Batangas'],
            ['name' => 'RJD TRADING', 'person' => 'MS. MA. ABEGAIL G. DAGTAY', 'address' => 'Poblacion 3, Laurel, Batangas'],
            ['name' => 'RIESAN FOOD CORNER', 'person' => 'MS. SUSANA R. BAGOS', 'address' => 'Alangilan, Batangas City'],
            ['name' => 'RMA LIGHTS AND SOUNDS', 'person' => 'MS. NELIA SUAREZ', 'address' => 'Salaban II, Ibaan, Batangas'],
            ['name' => 'ZYRE PHARMACEUTICALS CORPORATION', 'person' => 'MS. JELLA ESMERALDA DELOS REYES', 'address' => 'Universal Motors Corp., Bldg. 2232 Chino Roces Ave. Bangkal, Makati City'],
            ['name' => 'UNYOK AUTO SERVICE CENTER', 'person' => 'MR. EFREN HERNANDEZ JR.', 'address' => 'Pob. 2, Calatagan, Batangas'],
            ['name' => 'TUKADOR CONSUMER GOODS TRADING', 'person' => 'MS. CHERYL R. MENDOZA', 'address' => 'Camella Homes, Sambat, San Pascual, Batangas'],
            ['name' => "THREE J'S CRAVINGS LUTONG BAHAY", 'person' => 'MR. BERNARDO C. ARELLANO JR.', 'address' => 'Purok 1 Brgy. Dalig, Batangas City'],
            ['name' => 'TAN VILLE GARDEN RESORT', 'person' => 'MS. MERLIE M. TAN', 'address' => 'Namunga, Rosario, Batangas'],
            ['name' => 'SPEEDPRO AUTO CARE CENTER', 'person' => 'MR. EMERSON L. TAN', 'address' => 'Pallocan West, Batangas City'],
            ['name' => 'SOTOGRANDE BATANGAS HOTEL INC.', 'person' => 'MR. DENNIS L. BAUTISTA', 'address' => 'Manghinao Uno, Bauan, Batangas'],
            ['name' => 'SMARTBIZ TRADING & GENERAL MERCHANDISE', 'person' => 'MS. WHERLY G. MANANSALA', 'address' => '#10 Buenafe St., Brgy. 19, Batangas City'],
            ['name' => 'SAHARA AUTO SUPPLY CORP.', 'person' => 'MS. HILDA R. ARCEO', 'address' => 'Ilustre Ave., Lemery, Batangas'],
            ['name' => 'SABANG IBAAN MULTIPURPOSE COOPERATIVE', 'person' => 'MS. NELIA C. CASTILLO', 'address' => 'Sabang, Ibaan Batangas'],
        ];

        foreach ($suppliers as $index => $entry) {
            $name = trim((string) ($entry['name'] ?? ''));
            $person = trim((string) ($entry['person'] ?? ''));
            $address = trim((string) ($entry['address'] ?? ''));
            $sequence = $index + 1;

            $generatedContactPerson = $person !== ''
                ? $person
                : sprintf('MR./MS. CONTACT %03d', $sequence);
            $generatedContactNumber = sprintf('09%09d', 100000000 + $sequence);
            $generatedTin = sprintf(
                '%03d-%03d-%03d-%03d',
                ($sequence * 11) % 1000,
                ($sequence * 17) % 1000,
                ($sequence * 23) % 1000,
                ($sequence * 29) % 1000,
            );

            $emailBase = strtolower((string) preg_replace('/[^a-z0-9]+/i', '.', $name));
            $emailBase = trim((string) preg_replace('/\.+/', '.', $emailBase), '.');
            $generatedEmail = sprintf('%s.%03d@imaginary-suppliers.test', $emailBase !== '' ? $emailBase : 'supplier', $sequence);
            $generatedRemarks = sprintf('Auto-generated placeholder profile for seeded supplier #%03d.', $sequence);

            if ($name === '') {
                continue;
            }

            Supplier::firstOrCreate(
                [
                    'name' => $name,
                    'contact_person' => $generatedContactPerson,
                    'address' => $address !== '' ? $address : null,
                ],
                [
                    'proprietor' => $generatedContactPerson,
                    'authorized_representative' => $generatedContactPerson,
                    'owner' => $generatedContactPerson,
                    'contact_number' => $generatedContactNumber,
                    'email' => $generatedEmail,
                    'tin' => $generatedTin,
                    'remarks' => $generatedRemarks,
                    'is_active' => true,
                ]
            );
        }
    }
}
