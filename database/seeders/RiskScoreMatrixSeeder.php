<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiskScoreMatrixSeeder extends Seeder
{
    public function run()
    {
        $csvData = <<<CSV
"West Bank (Palestinian Territory, Occupied)","48,5",High,,,,,,,,,,,,,High,5
            Afghanistan,24,High,,,,,16,High,"9,1",High,X,,X,,Prohibited,10
            Åland Islands,"85,4",Low,,,,,,,,,,,,,Low,1
            Albania,"49,1",High,5.72,Medium,,X,35,Medium,,,,,,,High,5
            Algeria,"65,6",Medium,,,,,33,Medium,"4,4",Medium,,,,,Medium,2.5
            American Samoa,"75,8",Low,,,,,,,,,,X,,,High,5
            Andorra,"80,8",Low,2.73,Low,,,,,,,,,,,Low,1
            Angola,"67,4",Medium,,,,,29,High,"0,3",Low,,,,,High,5
            Anguilla,"72,4",Low,,,,,,,,,,X,,,High,5
            Antigua and Barbuda,"70,3",Low,4.95,Medium,,,,,,,,X,,,High,5
            Argentina,"69,3",Medium,,,,,38,Medium,"2,7",Low,,,,,Medium,2.5
            Armenia,69,Medium,4.63,Medium,,,49,Medium,,,,,,,Medium,2.5
            Aruba,"70,6",Low,,,,,,,,,,X,,,High,5
            Australia,"77,9",Low,3.75,Low,,,73,Low,"2,4",Low,,,,X,Low,1
            Austria,"77,5",Low,4.42,Low,,,74,Low,"3,3",Low,,,,X,Low,1
            Azerbaijan,"63,7",Medium,,,,,30,Medium,,,,,,,Medium,2.5
            Azores,,,,,,,,High,,,,X,,,High,5
            Bahamas,"69,1",Medium,6.46,Medium,,,64,Low,,,,X,,,High,5
            Bahrain,"69,4",Medium,4.5,Low,,,42,Medium,"2,1",Low,,X,,,High,5
            Bangladesh,"68,1",Medium,5.84,Medium,,,26,High,"4,4",Medium,,,,,High,5
            Barbados,"54,5",Medium,5.82,Medium,,X,65,Low,,,X,X,,,High,5
            Belarus,"63,2",Medium,5.04,Medium,,,41,Medium,,,,,X,,Prohibited,10
            Belgium,"76,6",Low,3.94,Low,,,73,Low,"1,7",Low,,,,X,Low,1
            Belize,"67,6",Medium,,,,,,,,,,X,,,High,5
            Benin,"64,8",Medium,,,,,42,Medium,"3,2",Low,,,,,Medium,2.5
            Bermuda,"78,2",Low,4.75,Medium,,,,,,,,X,,,High,5
            Bhutan,"77,1",Low,6.24,Medium,,,68,Low,,,,,,,Low,1
            Bolivia,"65,3",Medium,,,,,30,Medium,,,,,,,Medium,2.5
            "Bonaire, Sint Eustatius and Saba","75,6",Low,,,,,,,,,,,,,Low,1
            Bosnia-Herzegovina,59,Medium,,,,,35,Medium,,,,,X,,Prohibited,10
            Botswana,"75,8",Low,4.87,Medium,,,55,Medium,,,,X,,,High,5
            Brazil,"60,2",Medium,,,,,38,Medium,"0,9",Low,,,,,Medium,2.5
            British Indian Ocean Territory,"80,1",Low,,,,,,,,,,,,,Low,1
            British Virgin Islands,"66,1",Medium,,,,,,,,,,X,,,High,5
            Brunei Darussalam,"80,2",Low,,,,,,,,,,X,,,High,5
            Bulgaria,"71,3",Low,,,,,42,Medium,,,,,,X,Low,1
            Burkina Faso,52,Medium,6.77,High,,X,42,Medium,"8,3",High,X,,,,High,5
            Burundi,"50,3",Medium,,,,,19,High,"4,3",Medium,,,X,,Prohibited,10
            Cambodia,55,Medium,7.13,High,,X,23,High,,,X,,,,High,5
            Cameroon,"61,9",Medium,,,,,27,High,"7,4",High,,,,,High,5
            Canada,"74,2",Low,4.67,Medium,,,74,Low,"3,9",Low,,,,X,Low,1
            Cape Verde,"72,9",Low,6.49,Medium,,,58,Medium,,,,,,,Medium,2.5
            Cayman Islands,"57,8",Medium,7.66,High,,X,,,,,X,X,,,High,5
            Central African Rep,"50,6",Medium,,,,,24,High,,,,,X,,Prohibited,10
            Chad,60,Medium,,,,,20,High,"6,4",High,,,,,High,5
            Chile,"71,8",Low,,,,,67,Low,"6,5",High,,,,X,High,5
            China,"61,4",Medium,6.7,High,,,45,Medium,"1,9",Low,,,,,High,5
            Christmas Island,"80,3",Low,,,,,,,,,,,,,Low,1
            Cocos (Keeling) Islands,"80,3",Low,,,,,,,,,,,,,Low,1
            Colombia,"62,5",Medium,4.64,Medium,,,39,Medium,"7,1",High,,,,X,High,5
            Comoros,"65,3",Medium,,,,,20,High,,,,,,,High,5
            Congo (Brazzaville),"66,8",Medium,,,,,21,High,,,,,,,High,5
            "Congo, the Democratic Republic","42,9",High,,,,,19,High,6.7,High,,,X,,Prohibited,10
            Cook Islands,"75,1",Low,,,,,,,,,,X,,,High,5
            Costa Rica,"71,9",Low,4.74,Medium,,,58,Medium,,,,X,,X,High,5
            Cote D'Ivoire,"61,8",Medium,,,,,36,Medium,"4,3",Medium,,,,,Medium,2.5
            Croatia,"68,9",Medium,,,,,47,Medium,,,,,,X,Medium,2.5
            Cuba,54,Medium,5.75,Medium,,,46,Medium,,,,,X,,Prohibited,10
            Curacao,"67,4",Medium,,,,,,,,,,X,,,High,5
            Cyprus,"69,7",Medium,4.95,Medium,,,53,Medium,"0,9",Low,,,,X,Medium,2.5
            Czech Republic,"77,1",Low,4.28,Low,,,54,Medium,"0,3",Low,,,,X,Low,1
            Denmark,"83,9",Low,3.46,Low,,,88,Low,"0,3",Low,,,,X,Low,1
            Djibouti,"64,9",Medium,,,,,30,Medium,,,,X,,,High,5
            Dominica�,63,Medium,,,,,55,Medium,,,,X,,,High,5
            Dominican Republic,71,Low,4.72,Medium,,,30,Medium,,,,,,,Medium,2.5
            Ecuador,"63,2",Medium,,,,,36,Medium,,,,,,,Medium,2.5
            Egypt,"66,1",Medium,5.19,Medium,,,33,Medium,"6,9",High,,,,,High,5
            El Salvador,"68,2",Medium,,,,,34,Medium,,,,,,,Medium,2.5
            Equatorial Guinea,"67,9",Medium,,,,,17,High,,,,,,,High,5
            Eritrea,"55,1",Medium,,,,,22,High,,,,,,,High,5
            Estonia,"81,7",Low,,,,,74,Low,,,,,,X,Low,1
            Ethiopia,"65,2",Medium,6.77,High,,,39,Medium,"3,8",Low,,,X,,Prohibited,10
            Falkland Islands (Malvinas),"80,1",Low,,,,,,,,,,,,,Low,1
            Faroe islands,"84,2",Low,,,,,,,,,,,,,Low,1
            Fiji,"75,3",Low,5.56,Medium,,,55,Medium,,,,X,,,High,5
            Finland,"84,4",Low,3.06,Low,,,88,Low,1,Low,,,,X,Low,1
            France,"75,7",Low,,,,,71,Low,"4,6",Medium,,,,X,Low,1
            French Guiana,"80,1",Low,,,,,,,,,,,,,Low,1
            French Polynesia,"80,3",Low,,,,,,,,,,X,,,High,5
            Gabon,"68,3",Medium,,,,,31,Medium,"0,3",Low,,,,,Medium,2.5
            Gambia,72,Low,,,,,37,Medium,,,,,,,Medium,2.5
            Gaza Strip,"48,5",High,,,,,,,,,,,,,High,5
            Georgia,75,Low,4.82,Medium,,,55,Medium,,,,,,,Medium,2.5
            Germany,"73,3",Low,,,,,80,Low,"4,7",Medium,,,,X,Low,1
            Ghana,71,Low,4.88,Medium,,,43,Medium,,,,,,,Medium,2.5
            Gibraltar,"59,7",Medium,,,,X,,,,,,X,,,High,5
            Greece,"72,3",Low,3.67,Low,,,49,Medium,"4,8",Medium,,,,X,Low,1
            Greenland,"83,5",Low,,,,,,,,,,,,,Low,1
            Grenada,"71,9",Low,,,,,53,Medium,,,,,,,Medium,2.5
            Guadeloupe,"79,9",Low,,,,,,,,,,,,,Low,1
            Guam,75,Low,,,,,,,,,,,,,Low,1
            Guatemala,"69,2",Medium,5.12,Medium,,,25,High,,,,X,,,High,5
            Guernsey�,"77,6",Low,,,,,,,,,,X,,,High,5
            Guinea,56,Medium,,,,,25,High,,,,,X,,Prohibited,10
            Guinea Bissau,"51,7",Medium,,,,,21,High,,,,,X,,Prohibited,10
            Guyana,"66,9",Medium,,,,,39,Medium,,,,,,,Medium,2.5
            Haiti,"39,4",High,8.49,High ,,X,20,High,,,X,,,,High,5
            Honduras,"67,9",Medium,5.52,Medium,,,23,High,,,,,,,High,5
            Hong Kong,"70,5",Low,,,,,76,Low,,,,X,,,High,5
            Hungary,"75,5",Low,5.04,Medium,,,43,Medium,,,,,,X,Medium,2.5
            Iceland,"84,1",Low,4.16,Low,,,74,Low,,,,,,X,Low,1
            India,"62,8",Medium,,,,,40,Medium,"7,4",High,,,,,High,5
            Indonesia,"64,8",Medium,4.68,Medium,,,38,Medium,"5,5",Medium,,,,,Medium,2.5
            "Iran, Islamic Republic of","18,5",High,,,X,,25,High,5,Medium,X,,X,,Prohibited,10
            Iraq,45,High,,,,,23,High,"8,5",High,,,X,,Prohibited,10
            Ireland,"76,1",Low,4.45,Low,,,74,Low,"1,1",Low,,,,X,Low,1
            Isle Of Man,"76,6",Low,,,,,,,,,,X,,,High,5
            Israel,"60,3",Medium,3.83,Low,,,59,Medium,"4,8",Medium,,X,,X,High,5
            Italy,"71,6",Low,4.57,Low,,,56,Medium,"3,7",Low,,,,X,Low,1
            Jamaica,"53,8",Medium,5.77,Medium,,X,44,Medium,,,X,X,,,High,5
            Japan,"77,2",Low,4.99,Medium,,,73,Low,"1,5",Low,,,,X,Low,1
            Jersey�,"76,2",Low,,,,,,,,,,X,,,High,5
            Jordan,"54,4",Medium,5.6,Medium,,X,49,Medium,"2,6",Low,X,X,,,High,5
            Kazakhstan,"70,9",Low,,,,,37,Medium,,,,,,,Medium,2.5
            Kenya,"55,2",Medium,,,,,30,Medium,"6,2",High,,X,,,High,5
            Kiribati,"67,6",Medium,,,,,,,,,,,,,Medium,2.5
            Kosovo,"54,1",Medium,,,,,39,Medium,"0,3",Low,,,,,Medium,2.5
            Kuwait,"73,8",Low,,,,,43,Medium,,,,,,,Medium,2.5
            Kyrgyzstan,"69,3",Medium,6.09,Medium,,,27,High,,,,,,,High,5
            Lao People's Democratic Republic,"61,7",Medium,,,,,30,Medium,,,,,,,Medium,2.5
            Latvia,"79,5",Low,4.61,Medium,,,59,Medium,,,,,,X,Medium,2.5
            Lebanon,"46,3",High,,,,,24,High,"3,6",Low,,X,X,,Prohibited,10
            Lesotho,"71,5",Low,,,,,38,Medium,,,,,,,Medium,2.5
            Liberia,"57,9",Medium,,,,,29,High,,,,X,,,High,5
            Libya,"39,5",High,,,,,17,High,"5,1",Medium,,,X,,Prohibited,10
            Liechtenstein,"80,3",Low,,,,,,,,,,,,X,Low,1
            Lithuania,"81,2",Low,3.51,Low,,,61,Low,"0,8",Low,,,,X,Low,1
            Luxembourg,"76,3",Low,,,,,81,Low,,,,,,X,Low,1
            Macau,"76,7",Low,,,,,,,,,,,,,Low,1
            Macedonia,"65,3",Medium,,,,,39,Medium,,,,X,,,High,5
            Madagascar,"69,2",Medium,7.4,High ,,,26,High,,,,,,,High,5
            Malawi,77,Low,5.78,Medium,,,35,Medium,,,,,,,Medium,2.5
            Malaysia,"66,4",Medium,5.47,Medium,,,48,Medium,"2,2",Low,,X,,,High,5
            Maldives,"69,8",Medium,,,,,40,Medium,,,,X,,,High,5
            Mali,"33,3",High,7.37,High ,,X,29,High,"8,2",High,X,,X,,Prohibited,10
            Malta,"75,3",Low,5.45,Medium,,,54,Medium,,,,,,X,Medium,2.5
            Marshall Islands,"73,1",Low,,,,,,,,,,X,,,High,5
            Martinique,"79,9",Low,,,,,,,,,,,,,Low,1
            Mauritania,"73,9",Low,8.13,High ,,,28,High,"0,5",Low,,,,,High,5
            Mauritius,"75,4",Low,5.32,Medium,,,54,Medium,,,,X,,,High,5
            Mayotte,"80,3",Low,,,,,,,,,,,,,Low,1
            Mexico,"65,4",Medium,5.09,Medium,,,31,Medium,"2,4",Low,,,,X,Medium,2.5
            Micronesia,70,Low,,,,,,,,,,,,,Low,1
            Moldova,"66,3",Medium,4.98,Medium,,,36,Medium,,,,,,,Medium,2.5
            Monaco,74,Low,,,,,,,,,,,,,Low,1
            Mongolia,"76,7",Low,6.09,Medium,,,35,Medium,,,,,,,Medium,2.5
            Montenegro,"65,1",Medium,,,,,46,Medium,,,,,,,Medium,2.5
            Montserrat,"71,3",Low,,,,,,,,,,X,,,High,5
            Morocco,"51,8",Medium,5.32,Medium,,X,39,Medium,"1,2",Low,X,,,,High,5
            Mozambique,"55,6",Medium,7.71,High ,,,26,High,"7,4",High,,,,,High,5
            Myanmar,"30,6",High,7.83,High ,,X,28,High,,,X,,X,,Prohibited,10
            Namibia,"77,5",Low,,,,,49,Medium,,,,X,,,High,5
            Nauru,"71,6",Low,,,,,,,,,,X,,,High,5
            Nepal,"62,7",Medium,,,,,33,Medium,"4,7",Medium,,,,,Medium,2.5
            Netherlands,"70,5",Low,,,,,82,Low,"2,1",Low,,,,X,Low,1
            New Caledonia,"80,3",Low,,,,,,,,,,,,,Low,1
            New Zealand,"80,6",Low,3.53,Low,,,88,Low,"4,4",Medium,,,,X,Low,1
            Nicaragua,"41,4",High,6.75,High,,X,20,High,,,X,,X,,Prohibited,10
            Niger,64,Medium,,,,,31,Medium,"7,9",High,,,,,High,5
            Nigeria,57,Medium,,,,,24,High,"8,2",High,,,,,High,5
            Niue,"72,7",Low,,,,,,,,,,X,,,High,5
            Norfolk Island,"80,33",Low,,,,,,,,,,,,,Low,1
            North Korea,"21,9",High,,,X,,16,High,,,X,,X,,Prohibited,10
            North Mariana Islands,"75,6",Low,,,,,,,,,,,,,Low,1
            Norway,84,Low,3.35,Low,,,85,Low,"1,1",Low,,,,X,Low,1
            Oman,"77,9",Low,,,,,52,Medium,,,,,,,Medium,2.5
            Pakistan,"45,5",High,6,Medium,,X,28,High,"7,8",High,X,,,,High,5
            Palau,69,Medium,6.17,Medium,,,,,,,,X,,,High,5
            Panama,"47,8",High,6,Medium,,X,36,Medium,,,X,X,,,High,5
            Papua New Guinea,"69,1",Medium,,,,,31,Medium,,,,,,,Medium,2.5
            Paraguay,"59,1",Medium,,,,,30,Medium,"2,2",Low,,,,,Medium,2.5
            Peru,"66,1",Medium,4.5,Low,,,36,Medium,"4,5",Medium,,,,,Medium,2.5
            Philippines,"43,3",High,5.76,Medium,,X,33,Medium,"6,8",High,X,,,,High,5
            Pitcairn,"80,1",Low,,,,,,,,,,,,,Low,1
            Poland,"76,4",Low,,,,,56,Medium,,,,,,X,Low,1
            Portugal,"78,9",Low,3.85,Low,,,62,Low,,,,,,X,Low,1
            Puerto Rico,78,Low,,,,,,,,,,,,,Low,1
            Qatar�,"72,9",Low,,,,,63,Low,,,,X,,,High,5
            R�union,"79,9",Low,,,,,,,,,,,,,Low,1
            Romania,"72,5",Low,,,,,45,Medium,"1,1",Low,,,,X,Low,1
            Russian Federation,"42,3",High,,,,,29,High,"4,3",Medium,,X,X,,Prohibited,10
            Rwanda,"72,7",Low,,,,,53,Medium,"1,2",Low,,,,,Low,1
            Saint Berth�lemy,"80,3",Low,,,,,,,,,,,,,Low,1
            "Saint Helena, Ascension and Trista","80,1",Low,,,,,,,,,,X,,,High,5
            Saint Martin (French part),"80,3",Low,,,,,,,,,,X,,,High,5
            Saint Pierre and Miquelon,"80,3",Low,,,,,,,,,,X,,,High,5
            Samoa,"69,4",Medium,5.32,Medium,,,,,,,,X,,,High,5
            San Marino,"83,3",Low,3.42,Low,,,,,,,,,,,Low,1
            Sao Tome & Prin.,"68,2",Medium,,,,,45,Medium,,,,,,,Medium,2.5
            Saudi Arabia,"72,4",Low,5.12,Medium,,,53,Medium,"3,1",Low,,,,,Medium,2.5
            Senegal,53,Medium,7.25,High,,X,43,Medium,"1,6",Low,X,,,,High,5
            Serbia,"65,3",Medium,5.47,Medium,,,38,Medium,,,,,,,Medium,2.5
            Seychelles,"66,2",Medium,5.29,Medium,,,70,Low,,,,X,,,High,5
            Sierra Leone,"65,7",Medium,,,,,34,Medium,,,,,,,Medium,2.5
            Singapore,"78,8",Low,4.65,Medium,,,85,Low,,,,,,,Low,1
            Slovakia,"74,7",Low,4.37,Low,,,52,Medium,,,,,,X,Low,1
            Slovenia,"73,5",Low,3.3,Low,,,57,Medium,,,,,,X,Low,1
            Solomon Islands,"74,3",Low,6.74,High,,,43,Medium,,,,,,,High,5
            Somalia,"34,2",High,,,,,13,High,"8,4",High,,,X,,Prohibited,10
            South Africa,70,Medium,,,,,44,Medium,"1,2",Low,,,,,Medium,2.5
            South Korea�,"78,6",Low,,,,,62,Low,,,,,,X,Low,1
            South Sudan,"34,7",High,,,,X,,,,,X,,X,,Prohibited,10
            Spain,"74,5",Low,3.59,Low,,,61,Low,"2,9",Low,,,,X,Low,1
            Sri Lanka,"69,5",Medium,6.51,Medium,,,37,Medium,"5,4",Medium,,,,,Medium,2.5
            St Kitts & Nevis,"68,4",Medium,,,,,,,,,,X,,,High,5
            St Lucia,"65,6",Medium,,,,,56,Medium,,,,,,,Medium,2.5
            St Maarten,60,Medium,,,,,,,,,,,,,Medium,2.5
            St Vincent & Gren,"67,8",Medium,,,,,59,Medium,,,,,,,Medium,2.5
            Sudan,"66,9",Medium,,,,,20,High,"0,3",Low,,,X,,Prohibited,10
            Suriname,69,Medium,,,,,39,Medium,,,,,,,Medium,2.5
            Svalbard and Mayen,"85,1",Low,,,,,,,,,,,,,Low,1
            Swaziland (Eswatini),,,,,,,32,Medium,,,,,,,Medium,2.5
            Sweden,"82,4",Low,3.36,Low,,,85,Low,"1,7",Low,,,,X,Low,1
            Switzerland,"75,4",Low,4.89,Medium,,,84,Low,"2,5",Low,,,,X,Low,1
            Syria,"28,3",High,,,,X,13,High,"8,3",High,X,,X,,Prohibited,10
            Taiwan,"74,4",Low,4.39,Low,,,68,Low,"0,2",Low,,,,,Low,1
            Tajikistan,"63,7",Medium,5.97,Medium,,,25,High,4,Medium,,,,,High,5
            Tanzania,61,Medium,6.22,Medium,,,39,Medium,"4,5",Medium,,,,,Medium,2.5
            Thailand,"61,7",Medium,6.15,Medium,,,35,Medium,"5,7",Medium,,X,,,High,5
            Timor-Leste,71,Low,,,,,41,Medium,,,,,,,Medium,2.5
            Togo,"67,7",Medium,,,,,30,Medium,"1,2",Low,,,,,Medium,2.5
            Tokelau,"84,9",Low,,,,,,,,,,,,,Low,1
            Tonga,"72,6",Low,,,,,,,,,,X,,,High,5
            Trinidad & Tobago,"56,5",Medium,,,,,41,Medium,,,X,X,,,High,5
            Tunisia,"59,2",Medium,5.2,Medium,,,44,Medium,"4,4",Medium,,X,X,,Prohibited,10
            Turkey,"39,6",High,5.7,Medium,,X,38,Medium,"5,7",Medium,,X,,X,High,5
            Turkmenistan,"67,2",Medium,,,,,19,High,,,,,,,High,5
            Turks & Caicos,"68,7",Medium,,,,,,,,,,X,,,High,5
            Tuvalu,"69,4",Medium,,,,,,,,,,,,,Medium,2.5
            Uganda,"52,4",Medium,7.18,High,,X,27,High,"4,1",Medium,X,,,,High,5
            Ukraine,"55,1",Medium,5.21,Medium,,,32,Medium,"2,3",Low,,,,,Medium,2.5
            United Arab Emirates,"55,7",Medium,5.91,Medium,,X,69,Low,,,,,,,High,5
            United Kingdom,"72,4",Low,4.05,Low,,,78,Low,"4,7",Medium,,,,X,Low,1
            United States,"70,7",Low,4.6,Medium,,,67,Low,5,Medium,,,,X,Low,1
            United States Virgin Islands,"73,8",Low,,,,,,,,,,X,,,High,5
            Uruguay,"78,5",Low,3.98,Low,,,73,Low,"1,3",Low,,X,,,High,5
            Uzbekistan,"68,5",Medium,,,,,28,High,,,,,,,High,5
            Vanuatu,"57,8",Medium,5.33,Medium,,,45,Medium,,,X,X,,,High,5
            Vatican City State (Holy See),"81,7",Low,,,,,,,,,,,,,Low,1
            Venezuela,"48,9",High,,,,,14,High,4,Medium,,X,X,,Prohibited,10
            Vietnam,"63,6",Medium,,,,,39,Medium,"0,4",Low,,X,,,High,5
            Wallis and Futuna,"80,3",Low,,,,,,,,,,,,,Low,1
            Western Sahara,"51,8",Medium,,,,,,,,,,,,,Medium,2.5
            Yemen,"33,2",High,,,,X,16,High,"5,9",Medium,X,,X,,Prohibited,10
            Zambia,"75,1",Low,6.03,Medium,,,33,Medium,,,,,,,Medium,2.5
            Zimbabwe,"47,4",High,6.79,High,,,23,High,,,X,,X,,Prohibited,10
CSV;

        // Split the CSV data into rows
        $rows = explode("\n", $csvData);

        foreach ($rows as $row) {
            // Skip empty lines
            if (empty(trim($row))) continue;

            // Parse the CSV row
            $data = str_getcsv($row);
      
            DB::table('risk_score_matrix')->insert([
                'matrix_type' => 'countries',
                'title' => trim($data[0]),
                'know_your_customer' => $this->parseDecimal($data[1]),
                'kyc_score' => $data[2] ?? null,
                'basel_index' => $this->parseDecimal($data[3] ?? null),
                'basel_score' => $data[4] ?? null,
                'fatf_black_list' => $this->parseBoolean($data[5] ?? null),
                'fatf_grey_list' => $this->parseBoolean($data[6] ?? null),
                'transparency_corruption_index' => $this->parseInteger($data[7] ?? null),
                'transparency_score' => $data[8] ?? null,
                'global_terrorism_index' => $this->parseDecimal($data[9] ?? null),
                'global_terrorism_score' => $data[10] ?? null,
                'eu_commission_targeted' => $this->parseBoolean($data[11] ?? null),
                'eu_commission_targeted_territories' => $this->parseBoolean($data[12] ?? null),
                'eu_commission_sanctions' => $this->parseBoolean($data[13] ?? null),
                'eu_commission_member_state' => $this->parseBoolean($data[14] ?? null),
                'final_score' => $data[15] ?? null,
                'final_score_value' => $this->parseDecimal($data[16] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

                $csvData = <<<CSV
        Accountant / gatekeeper / auditor /advisor / tax advisor,High,5
        Adult entertainment without sexually oriented services,High,5
        Adult with sexually oriented services,Prohibited,10
        Alcohol and tobacco trading online,High,5
        Architectural and engineering activities / technical testing and analysis,Medium,2.5
        Arms trade and defence ,Prohibited,10
        Art dealing and other art related business,High,5
        ATM provider,Prohibited,10
        Auction platform,High,5
        Aviation / Aerospace,Medium,2.5
        Binary Options/CFDs and similar,Prohibited,10
        Business and other management consultancy activities,High,5
        Business loans services,High,5
        Car service (cash intensive business),High,5
        Cash Courier,Prohibited,10
        Cash intensive business (other type),High,5
        Casinos (land based/offline),High,5
        Charity / fundraising organisation,Prohibited,10
        Citizenship investment programs / investment residence,Prohibited,10
        "Company engaged in professional sports (e.g., investments in, and transfer agreements relating to, professional football players)",High,5
        Construction,High,5
        Consumer credit and low value loans,High,5
        Convenience store (cash intensive business),High,5
        Corporate banking services,High,5
        Counterfeit goods,High,5
        Courier in precious metals and stones,High,5
        Credit and financial institution ,High,5
        Crowdfunding platform operator ,High,5
        Currency exchange operator,High,5
        Dating services,High,5
        Development aid,High,5
        Dual-use goods with military perpuse,Prohibited,10
        Electronic money institution (EMI),High,5
        Energy sector,High,5
        Environmental services,Medium,2.5
        Escort services,Prohibited,10
        Financial broker ,High,5
        Fireworks or pyrotechnic articles or accessories,High,5
        Foreign exchange company / Forex broker,High,5
        Free ports,High,5
        Freight transport by road,High,5
        Gambling (betting) ,High,5
        Gambling (bingo),High,5
        Gambling (land-based/offline poker),High,5
        Gambling (lottery),High,5
        "Gambling (operator is ""crypto"" based accepting crypto, accessible through TOR, etc.)",High,5
        Gaming company,High,5
        Gaming machine (land based/offline and outside casinos),High,5
        "General e-commerce (electronics, tangible goods, furniture, clothing)",Low,1
        Get rich quick schemes,Prohibited,10
        Hawala related activities (Informal Transfer of Funds through Hawala),Prohibited,10
        "Hazardous chemicals, fuels",Prohibited,10
        Health and beauty,High,5
        High value goods (artefacts and antiquities),High,5
        High value goods (gold and diamonds),High,5
        High value goods (other than precious metals and precious stones),High,5
        Holding company,High,5
        Hotel and accommodation (no cash involved),Medium,2.5
        Illegal Drugs / Unauthorized prescription drugs,Prohibited,10
        Information service activities / data processing,High,5
        Insurance company (other type),High,5
        "Intellectual online e-commerce (photos, e-books, movies and music downloads)",Low,1
        Investment fund / company ,High,5
        Investments in real estate,High,5
        IT (software) development,High,5
        IT services and IT Software Consulting,High,5
        Language institutes,Low,1
        Legal professional / lawyer / notary,High,5
        Life Insurance ,High,5
        Luxury goods,High,5
        Manufacture of basic metals,Medium,2.5
        Manufacture of ceramic hausehold and ornamental articles,Low,1
        Manufacture of food products,Medium,2.5
        Manufacture of other builder's carpentry and joinery,Medium,2.5
        Manufacture of prepared pet foods,Medium,2.5
        Manufacture of refined petroleum products,High,5
        Marketing / digital marketing services,High,5
        Media / Entertainment,Medium,2.5
        Medical marijuana/cannabis,Prohibited,10
        Metal industry,High,5
        "Money Value Transfer Services (MVTS, MSB)",Prohibited,10
        Mortgage credit and high-value asset backed securities,High,5
        Non-life insurance company,High,5
        Non-Profit organisation,High,5
        Nutraceutical and dietary supplements,High,5
        Oil and gas,High,5
        Online gambling,High,5
        "Other personal service activities (e.g., hairdressing and beauty treatment, health care where no cash is involved)",Medium,2.5
        Over the Counter (OTC) investment,Prohibited,10
        Pawnbroker,High,5
        Payment institution (PSP),High,5
        Peer-to-peer lending platform operator,High,5
        Physical well-being activities,Low,1
        Piracy or illegal streaming,Prohibited,10
        Political / religious organisations or groups,Prohibited,10
        Private Banking / Wealth management,High,5
        Processing and preserving of meat and production of meat products,Medium,2.5
        Professional intermediary,High,5
        Prostitution services,Prohibited,10
        Public administration or enterprise ,High,5
        Public procurement,High,5
        Pyramid Schemes / multi-level marketing (MLM),Prohibited,10
        Real estate,High,5
        Recruitment / human resource management / employment services,High,5
        "Repair, maintenance and trade of motor vehicles",High,5
        Restaurant (cash intensive business),High,5
        Restaurant / tavern / bar and night club (no cash involved),Medium,2.5
        "Retail trade, except of motor vehicles and motorcycles",Medium,2.5
        Safe custody services (safety deposit box),High,5
        Sale of furniture / household goods / hardware and ironmongery,Low,1
        Sale of textile / clothing / fur / footwear and leather goods,Low,1
        Sawmilling and planing of wood,Low,1
        Shell / Offshore bank,Prohibited,10
        Shell company,Prohibited,10
        Sports including online sport,High,5
        Tickets and events,High,5
        Timber and building materials,Medium,2.5
        Tourism / travel activities,High,5
        Trade in oil/petroleum products,High,5
        Translation and interpretation activities,Low,1
        Transport / Logistics Services,High,5
        Trust / company service provider (TCSPs),High,5
        Virtual Asset Miner (mining),High,5
        Virtual Assets business,High,5
        Virtual assets services provider (VASP),High,5
        Wallet provider / software wallet provider,High,5
        "Weapons, ammunition, rifles and related goods",Prohibited,10
        Web-Hosting,High,5
        Wholesale of alcoholic beverages,High,5
        Wholesale of computer / computer peripheral equipment and software,Medium,2.5
        Wholesale of electronic / elecommunications equipment and parts,Medium,2.5
        Wholesale of information and communication equipment,Medium,2.5
        Wholesale of meat and meat products,Medium,2.5
        Wholesale of office furnitures,Low,1
        Wholesale of other household goods,Low,1
        Wholesale of pharmaceutical and nursing goods,High,5
        Wholesale of tobacco products,High,5
        CSV;
        $rows = explode("\n", $csvData);
        foreach ($rows as $row) {
            // Skip empty lines
            if (empty(trim($row))) continue;

            // Parse the CSV row
            $data = str_getcsv($row);
            //dd($data);
      
            DB::table('risk_score_matrix')->insert([
                'matrix_type' => 'industries',
                'title' => trim($data[0]),
                'final_score' => $data[1] ?? null,
                'final_score_value' => $this->parseDecimal($data[2] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
    }

    // Helper function to parse decimals
    private function parseDecimal($value)
    {
        return $value ? floatval(str_replace(',', '.', $value)) : null;
    }

    // Helper function to parse booleans (X, Prohibited, etc.)
    private function parseBoolean($value)
    {
        return in_array($value, ['X', 'Prohibited']);
    }

    // Helper function to parse integers
    private function parseInteger($value)
    {
        return $value ? intval($value) : null;
    }
}
