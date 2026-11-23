-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2026 at 06:19 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `amarahotel`
--
CREATE DATABASE IF NOT EXISTS `amarahotel` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `amarahotel`;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `First name` text NOT NULL,
  `Last name` text NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Contact` int(25) NOT NULL,
  `Room Type` varchar(50) NOT NULL,
  `Arrival Date` date NOT NULL,
  `Departure Date` date NOT NULL,
  `Adults` int(25) NOT NULL,
  `Children` int(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`First name`, `Last name`, `Email`, `Contact`, `Room Type`, `Arrival Date`, `Departure Date`, `Adults`, `Children`) VALUES
('Amanda', 'Kimmy', 'A@gmail.com', 723156789, 'family', '2024-07-14', '2024-07-28', 4, 1),
('Andrew', 'Drew', 'drew@gmail.com', 11293754, 'family', '2024-07-11', '2024-07-30', 4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `client feedback`
--

CREATE TABLE `client feedback` (
  `First name` text NOT NULL,
  `Last name` text NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Client Rating` varchar(60) NOT NULL,
  `Client feedback` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client feedback`
--

INSERT INTO `client feedback` (`First name`, `Last name`, `Email`, `Client Rating`, `Client feedback`) VALUES
('Amanda', 'Kariuki', 'A@gmail.com', 'Mid Service', 'There is room for improvement.'),
('Cynthia', 'Kamau', 'cynthia.kamau@strathmore.edu', 'Amazing service', 'It was amazing'),
('Florence', 'Wachira', 'flo@gmail.com', 'Amazing service', 'The service was very exquisite.'),
('M', 'K', 'M@gmail.com', 'Mid Service', 'There is a need for improvement.');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `username` varchar(15) NOT NULL,
  `email` varchar(10) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`username`, `email`, `password`) VALUES
('A', 'A@gmail.co', '1234'),
('Amanda', 'Aa@gmail.c', '1235'),
('Ck', 'ck@gmail.c', '1234'),
('cynthia', 'ck@gmail.c', '1234'),
('Drew', 'andrew@gma', '12345'),
('E', 'E@gmail.co', '1234'),
('Flo', 'flo@gmail.', '1234'),
('Mitch', 'M@gmail.co', '1234'),
('P', 'p@gmail.co', '1234');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `Name` text NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Password` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`Name`, `Username`, `Email`, `Password`) VALUES
('Andrew', 'Drew', 'andrew@gmail.com', '1234'),
('Cynthia', 'Ck', 'ck@gmail.com', '1234'),
('Essy', 'E', 'E@gmail.com', '1234'),
('Florence', 'Flo', 'flo@gmail.com', '1234'),
('Mitchelle', 'Mitch', 'M@gmail.com', '1234');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`Email`);

--
-- Indexes for table `client feedback`
--
ALTER TABLE `client feedback`
  ADD PRIMARY KEY (`Email`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`Email`);
--
-- Database: `multilingual_health_platform`
--
CREATE DATABASE IF NOT EXISTS `multilingual_health_platform` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `multilingual_health_platform`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('symptom_check','myth_check','article_view') NOT NULL,
  `detail` varchar(255) NOT NULL,
  `result` varchar(50) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `language` varchar(20) NOT NULL DEFAULT 'english',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chw_notes`
--

CREATE TABLE `chw_notes` (
  `id` int(11) NOT NULL,
  `chw_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_suggestions`
--

CREATE TABLE `content_suggestions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_education`
--

CREATE TABLE `health_education` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_sw` text DEFAULT NULL,
  `title_maa` text DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `content_sw` mediumtext DEFAULT NULL,
  `content_maa` mediumtext DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'other',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_education`
--

INSERT INTO `health_education` (`id`, `title`, `title_sw`, `title_maa`, `content`, `content_sw`, `content_maa`, `category`, `created_at`) VALUES
(1, 'Antenatal Care Visits', 'Ziara za Kliniki ya Wajawazito', NULL, 'Antenatal care is the health check-up a woman receives during pregnancy. The World Health Organization recommends at least eight antenatal contacts, with the first visit within the first 12 weeks of pregnancy. At each visit the health worker checks the mother\'s blood pressure, weight and blood, checks the baby\'s growth, and gives tetanus vaccination, iron and folic acid tablets, and a mosquito net. These visits help to find problems early, such as high blood pressure, anaemia or infections, before they become dangerous. Every pregnant woman should start clinic visits early and keep all her appointments.', 'Huduma ya kliniki ya wajawazito ni uchunguzi wa afya ambao mama hupata wakati wa ujauzito. Shirika la Afya Duniani linapendekeza angalau mahudhurio manane ya kliniki, na ziara ya kwanza ifanyike ndani ya wiki 12 za kwanza za ujauzito. Katika kila ziara, mhudumu wa afya hupima shinikizo la damu, uzito na damu ya mama, huangalia ukuaji wa mtoto, na hutoa chanjo ya pepopunda, vidonge vya madini ya chuma na asidi ya foliki, pamoja na chandarua. Ziara hizi husaidia kugundua matatizo mapema, kama shinikizo la juu la damu, upungufu wa damu au maambukizi, kabla hayajawa hatari. Kila mama mjamzito anapaswa kuanza kliniki mapema na kuhudhuria kila miadi.', NULL, 'maternal', '2026-11-23 20:13:01'),
(2, 'Danger Signs During Pregnancy', 'Dalili za Hatari Wakati wa Ujauzito', NULL, 'Some signs during pregnancy mean that the mother or baby is in danger and needs care immediately. These include bleeding from the vagina, a severe headache or blurred vision, swelling of the face and hands, fits (convulsions), fever, severe pain in the abdomen, the baby moving less or not at all, and water breaking before labour starts. If a pregnant woman has any of these signs, she should go to the nearest health facility at once. Family members and neighbours should help her to travel quickly. Do not wait for the next clinic day.', 'Baadhi ya dalili wakati wa ujauzito zinaonyesha kuwa mama au mtoto yuko hatarini na anahitaji huduma mara moja. Dalili hizi ni pamoja na kutokwa na damu ukeni, maumivu makali ya kichwa au kuona ukungu, kuvimba uso na mikono, degedege, homa, maumivu makali ya tumbo, mtoto kucheza kidogo au kutocheza kabisa, na maji kupasuka kabla ya uchungu kuanza. Mama mjamzito akiwa na dalili yoyote kati ya hizi, aende kwenye kituo cha afya kilicho karibu mara moja. Wanafamilia na majirani wamsaidie kusafiri haraka. Usisubiri siku inayofuata ya kliniki.', NULL, 'maternal', '2026-11-23 20:13:01'),
(3, 'Safe Delivery', 'Kujifungua Salama', NULL, 'Giving birth in a health facility with a skilled health worker is the safest choice for both the mother and the baby. Skilled health workers can manage heavy bleeding, a long or difficult labour, and a baby who does not breathe well at birth, which are some of the main causes of death for mothers and newborns. Plan early: know which facility you will use, save money for transport, and arrange who will take you there and who will care for the home. Pack clean clothes for the mother and baby in advance. Under Kenya\'s free maternity services, delivery in public health facilities is free.', 'Kujifungua katika kituo cha afya mbele ya mhudumu wa afya aliye na ujuzi ndilo chaguo salama zaidi kwa mama na mtoto. Wahudumu hawa wanaweza kudhibiti kutokwa na damu nyingi, uchungu wa muda mrefu au mgumu, na mtoto asiyepumua vizuri anapozaliwa, ambazo ni baadhi ya sababu kuu za vifo vya akina mama na watoto wachanga. Panga mapema: jua kituo utakachotumia, weka akiba ya nauli, na upange nani atakupeleka na nani atatunza nyumbani. Andaa mapema nguo safi za mama na mtoto. Chini ya mpango wa Kenya wa huduma za uzazi bila malipo, kujifungua katika vituo vya afya vya umma ni bure.', NULL, 'maternal', '2026-11-23 20:13:01'),
(4, 'Postnatal Care for Mother and Baby', 'Huduma Baada ya Kujifungua kwa Mama na Mtoto', NULL, 'The first six weeks after birth are an important time for both mother and baby. The mother and baby should be checked by a health worker within 48 hours of birth, and again at about one week and six weeks. The mother should go to a health facility immediately if she has heavy bleeding, fever, a foul-smelling discharge, severe headache, or pain and swelling in the legs. Feelings of deep sadness that do not go away should also be discussed with a health worker. Postnatal visits are also a good time to start the baby\'s vaccinations and to discuss family planning.', 'Wiki sita za kwanza baada ya kujifungua ni kipindi muhimu kwa mama na mtoto. Mama na mtoto wanapaswa kuchunguzwa na mhudumu wa afya ndani ya saa 48 baada ya kuzaliwa, kisha tena baada ya takriban wiki moja na wiki sita. Mama aende kituo cha afya mara moja akiwa na damu nyingi, homa, uchafu wenye harufu mbaya, maumivu makali ya kichwa, au maumivu na uvimbe miguuni. Hisia za huzuni kubwa zisizoisha pia zijadiliwe na mhudumu wa afya. Ziara hizi pia ni wakati mzuri wa kuanza chanjo za mtoto na kujadili upangaji uzazi.', NULL, 'maternal', '2026-11-23 20:13:01'),
(5, 'Childhood Immunization', 'Chanjo za Watoto', NULL, 'Vaccines protect children from serious diseases such as measles, polio, tuberculosis, whooping cough, tetanus and pneumonia. In Kenya, children receive vaccines at birth, at 6, 10 and 14 weeks, at 6 months, at 9 months and at 18 months. Vaccines are safe and free at public health facilities. A child may have a mild fever or soreness after a vaccine, which goes away within a day or two. Keep the child\'s clinic card safe and bring it to every visit so that no vaccine is missed.', 'Chanjo huwakinga watoto dhidi ya magonjwa hatari kama surua, polio, kifua kikuu, kifaduro, pepopunda na nimonia. Nchini Kenya, watoto hupata chanjo wanapozaliwa, katika wiki ya 6, 10 na 14, katika miezi 6, miezi 9 na miezi 18. Chanjo ni salama na hutolewa bure katika vituo vya afya vya umma. Mtoto anaweza kupata homa kidogo au maumivu baada ya kuchanjwa, ambayo huisha ndani ya siku moja au mbili. Hifadhi kadi ya kliniki ya mtoto vizuri na uilete kila unapokuja ili mtoto asikose chanjo yoyote.', NULL, 'child', '2026-11-23 20:13:01'),
(6, 'Exclusive Breastfeeding', 'Kunyonyesha Maziwa ya Mama Pekee', NULL, 'Breast milk is the best food for a baby. For the first six months, a baby should be given breast milk only, with no water, tea, porridge or other food. The first yellow milk, called colostrum, is very nutritious and protects the baby against infections, so it should be given and not thrown away. Start breastfeeding within one hour of birth and feed the baby whenever it wants, day and night. From six months, give other foods while continuing to breastfeed up to two years or beyond.', 'Maziwa ya mama ndicho chakula bora zaidi kwa mtoto. Kwa miezi sita ya kwanza, mtoto apewe maziwa ya mama pekee, bila maji, chai, uji wala chakula kingine. Maziwa ya kwanza ya rangi ya njano, yanayoitwa dang\'a, yana virutubisho vingi na humkinga mtoto dhidi ya maambukizi, kwa hiyo mtoto apewe maziwa hayo na yasitupwe. Anza kunyonyesha ndani ya saa moja baada ya kuzaliwa na umnyonyeshe mtoto kila anapotaka, mchana na usiku. Kuanzia miezi sita, mpe vyakula vingine huku ukiendelea kunyonyesha hadi miaka miwili au zaidi.', NULL, 'child', '2026-11-23 20:13:01'),
(7, 'Managing Diarrhoea in Children', 'Kudhibiti Kuhara kwa Watoto', NULL, 'Diarrhoea makes a child lose water and salts from the body, which can quickly become dangerous. Give the child Oral Rehydration Salts (ORS), mixed with clean water exactly as written on the packet, after every loose stool. Get zinc tablets from a health facility and give them for 10 to 14 days, even after the diarrhoea stops. Continue breastfeeding and giving food. Take the child to a health facility immediately if there is blood in the stool, if the child cannot drink, keeps vomiting, has sunken eyes, or becomes very weak or sleepy.', 'Kuhara humfanya mtoto apoteze maji na chumvi mwilini, jambo ambalo linaweza kuwa hatari haraka. Mpe mtoto chumvi ya kunywa ya ORS, iliyochanganywa na maji safi kama ilivyoandikwa kwenye pakiti, baada ya kila choo cha majimaji. Pata vidonge vya zinki kutoka kituo cha afya na umpe kwa siku 10 hadi 14, hata baada ya kuhara kukoma. Endelea kunyonyesha na kumpa chakula. Mpeleke mtoto kituo cha afya mara moja ikiwa kuna damu kwenye choo, hawezi kunywa, anaendelea kutapika, macho yamezama, au amedhoofika sana au ana usingizi mwingi.', NULL, 'child', '2026-11-23 20:13:01'),
(8, 'Danger Signs in Newborns', 'Dalili za Hatari kwa Watoto Wachanga', NULL, 'Newborn babies can become very sick quickly. Take the baby to a health facility immediately if the baby is not feeding well, has fits, is breathing fast or with difficulty, has a fever or feels very cold, has yellow skin or eyes in the first day or after two weeks, or has redness, pus or a bad smell around the cord. Keep the baby warm by holding it skin to skin and covering its head. Keep the cord clean and dry and do not put anything on it.', 'Watoto wachanga wanaweza kuugua sana kwa haraka. Mpeleke mtoto kituo cha afya mara moja ikiwa hanyonyi vizuri, ana degedege, anapumua haraka au kwa shida, ana homa au ana baridi sana, ana ngozi au macho ya njano katika siku ya kwanza au baada ya wiki mbili, au ana wekundu, usaha au harufu mbaya kwenye kitovu. Mweke mtoto katika joto kwa kumkumbatia ngozi kwa ngozi na kumfunika kichwa. Weka kitovu kikiwa safi na kikavu na usipake chochote.', NULL, 'child', '2026-11-23 20:13:01'),
(9, 'Malaria Prevention', 'Kuzuia Malaria', NULL, 'Malaria is caused by a parasite that is passed to people through the bite of an infected female Anopheles mosquito, mostly at night. It is not caused by cold weather, rain or eating certain foods. Sleep under a long-lasting insecticide-treated net every night, especially pregnant women and children under five. Remove standing water around the home where mosquitoes breed. If you have a fever, get tested at a health facility, because malaria can be confirmed and treated quickly. Pregnant women should also take the preventive malaria medicine given at the antenatal clinic.', 'Malaria husababishwa na vimelea vinavyoambukizwa kwa binadamu kupitia kuumwa na mbu jike wa Anopheles aliyeambukizwa, hasa usiku. Haisababishwi na hali ya baridi, mvua wala kula vyakula fulani. Lala ndani ya chandarua chenye dawa kila usiku, hasa wanawake wajawazito na watoto walio chini ya miaka mitano. Ondoa maji yaliyotuama karibu na nyumba ambapo mbu huzaliana. Ukiwa na homa, pima katika kituo cha afya, kwa sababu malaria inaweza kuthibitishwa na kutibiwa haraka. Wanawake wajawazito pia wanywe dawa ya kuzuia malaria wanayopewa kliniki.', NULL, 'disease', '2026-11-23 20:13:01'),
(10, 'Tuberculosis (TB)', 'Kifua Kikuu (TB)', NULL, 'Tuberculosis, or TB, is an infection caused by bacteria that usually affects the lungs. It spreads through the air when a person with TB coughs or sneezes. It is not caused by a curse or witchcraft. Signs include a cough lasting two weeks or more, coughing blood, fever, night sweats and weight loss. TB testing and treatment are free in Kenya, and TB can be cured with six months of medicine. It is very important to take the medicine every day until the end, even after feeling better.', 'Kifua kikuu, au TB, ni maambukizi yanayosababishwa na bakteria ambayo kwa kawaida huathiri mapafu. Huenea kupitia hewa mtu mwenye TB anapokohoa au kupiga chafya. Haisababishwi na laana wala uchawi. Dalili ni pamoja na kikohozi kinachodumu wiki mbili au zaidi, kukohoa damu, homa, kutokwa na jasho usiku na kupungua uzito. Kupima na kutibu TB ni bure nchini Kenya, na TB inaweza kutibika kwa dawa za miezi sita. Ni muhimu sana kumeza dawa kila siku hadi mwisho, hata baada ya kujisikia vizuri.', NULL, 'disease', '2026-11-23 20:13:01'),
(11, 'HIV Prevention and Testing', 'Kuzuia na Kupima VVU', NULL, 'HIV is a virus that weakens the body\'s defences against disease. It is passed through unprotected sex, infected blood, sharing needles or sharp tools, and from a mother to her baby during pregnancy, birth or breastfeeding. It is not passed through mosquito bites, sharing food, hugging or shaking hands. Know your status by testing at a health facility; testing is free and confidential. People living with HIV who take antiretroviral treatment every day can live long, healthy lives, and pregnant women on treatment can protect their babies from infection. Herbs cannot cure HIV.', 'VVU ni virusi vinavyodhoofisha kinga ya mwili dhidi ya magonjwa. Huambukizwa kupitia ngono isiyo salama, damu yenye maambukizi, kuchangia sindano au vifaa vyenye ncha kali, na kutoka kwa mama kwenda kwa mtoto wakati wa ujauzito, kujifungua au kunyonyesha. Haviambukizwi kwa kuumwa na mbu, kula pamoja, kukumbatiana wala kusalimiana kwa mkono. Jua hali yako kwa kupima katika kituo cha afya; kupima ni bure na ni siri. Watu wanaoishi na VVU wanaotumia dawa za kufubaza virusi kila siku wanaweza kuishi maisha marefu na yenye afya, na wanawake wajawazito wanaotumia dawa wanaweza kuwakinga watoto wao. Dawa za mitishamba haziwezi kutibu VVU.', NULL, 'disease', '2026-11-23 20:13:01'),
(12, 'Brucellosis and Raw Milk', 'Ugonjwa wa Brusela na Maziwa Mabichi', NULL, 'Brucellosis is an infection that people get from cattle, goats, sheep and camels, mainly by drinking raw milk or eating undercooked meat from infected animals, and by handling animals during birth. Signs include a fever that comes and goes, sweating, tiredness, headache and painful joints. To prevent it, always boil milk before drinking it, cook meat well, and wear gloves or wash your hands well after helping animals give birth. Anyone with a fever that keeps returning should be tested at a health facility, because brucellosis needs specific treatment.', 'Brusela ni maambukizi ambayo binadamu hupata kutoka kwa ng\'ombe, mbuzi, kondoo na ngamia, hasa kwa kunywa maziwa mabichi au kula nyama isiyoiva vizuri kutoka kwa wanyama walioambukizwa, na kwa kushika wanyama wanapozaa. Dalili ni pamoja na homa inayokuja na kuondoka, kutokwa na jasho, uchovu, maumivu ya kichwa na maumivu ya viungo. Ili kuzuia, chemsha maziwa kila mara kabla ya kunywa, pika nyama hadi iive vizuri, na vaa glavu au osha mikono vizuri baada ya kusaidia wanyama kuzaa. Mtu yeyote mwenye homa inayorudi mara kwa mara apime katika kituo cha afya, kwa sababu brusela inahitaji matibabu maalum.', NULL, 'disease', '2026-11-23 20:13:01'),
(13, 'Trachoma and Eye Health', 'Trakoma na Afya ya Macho', NULL, 'Trachoma is an eye infection that is common in dry areas and can cause blindness if it keeps coming back. It spreads through flies and through dirty hands, towels and clothes that touch the eyes. Wash children\'s faces with clean water every day, especially around the eyes and nose. Use a latrine and keep rubbish away from the home to reduce flies. Do not share towels. Anyone with red, itchy, painful or sticky eyes, or eyelashes turning inwards, should be checked at a health facility.', 'Trakoma ni maambukizi ya macho yanayotokea sana katika maeneo kame na yanaweza kusababisha upofu yakijirudia. Huenezwa na nzi pamoja na mikono, taulo na nguo chafu zinazogusa macho. Osha nyuso za watoto kwa maji safi kila siku, hasa kuzunguka macho na pua. Tumia choo na weka taka mbali na nyumba ili kupunguza nzi. Usichangie taulo. Mtu yeyote mwenye macho mekundu, yanayowasha, yanayouma au yenye utando, au kope zinazogeuka kuelekea ndani, achunguzwe katika kituo cha afya.', NULL, 'disease', '2026-11-23 20:13:01'),
(14, 'Nutrition During Pregnancy', 'Lishe Wakati wa Ujauzito', NULL, 'A pregnant woman needs more nutritious food to support her own health and the growth of her baby. She should eat an extra meal each day, with a mix of foods: grains such as maize or rice, beans and lentils, milk, eggs, meat or fish, and vegetables and fruits. Eggs, milk and meat are safe and healthy during pregnancy. She should take the iron and folic acid tablets given at the clinic to prevent anaemia, drink plenty of clean water, and avoid alcohol and smoking.', 'Mama mjamzito anahitaji chakula chenye virutubisho zaidi ili kulinda afya yake na kusaidia ukuaji wa mtoto. Anapaswa kula mlo mmoja wa ziada kila siku, wenye mchanganyiko wa vyakula: nafaka kama mahindi au mchele, maharagwe na dengu, maziwa, mayai, nyama au samaki, pamoja na mboga na matunda. Mayai, maziwa na nyama ni salama na yenye afya wakati wa ujauzito. Anywe vidonge vya madini ya chuma na asidi ya foliki anavyopewa kliniki ili kuzuia upungufu wa damu, anywe maji safi ya kutosha, na aepuke pombe na uvutaji wa sigara.', NULL, 'nutrition', '2026-11-23 20:13:01'),
(15, 'Child Nutrition', 'Lishe ya Watoto', NULL, 'Good nutrition helps children grow strong, learn well and fight infections. From six months of age, a baby needs other foods in addition to breast milk. Start with soft foods such as thick porridge and mashed foods, and slowly add a variety: grains, beans, milk, eggs, meat, vegetables and fruits. Feed young children small meals several times a day, as their stomachs are small. Take children for growth monitoring at the clinic, where their weight and height are checked, so that malnutrition can be found and treated early.', 'Lishe bora huwasaidia watoto kukua wakiwa na nguvu, kujifunza vizuri na kupambana na maambukizi. Kuanzia umri wa miezi sita, mtoto anahitaji vyakula vingine pamoja na maziwa ya mama. Anza na vyakula laini kama uji mzito na vyakula vilivyopondwa, kisha ongeza polepole aina mbalimbali: nafaka, maharagwe, maziwa, mayai, nyama, mboga na matunda. Walishe watoto wadogo milo midogo mara kadhaa kwa siku, kwa sababu matumbo yao ni madogo. Wapeleke watoto kliniki kupimwa ukuaji, ambapo uzito na urefu wao hupimwa, ili utapiamlo ugunduliwe na kutibiwa mapema.', NULL, 'nutrition', '2026-11-23 20:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `myth_facts`
--

CREATE TABLE `myth_facts` (
  `id` int(11) NOT NULL,
  `myth` text NOT NULL,
  `myth_sw` text DEFAULT NULL,
  `myth_maa` text DEFAULT NULL,
  `fact` text NOT NULL,
  `fact_sw` text DEFAULT NULL,
  `fact_maa` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'other',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `myth_facts`
--

INSERT INTO `myth_facts` (`id`, `myth`, `myth_sw`, `myth_maa`, `fact`, `fact_sw`, `fact_maa`, `category`, `created_at`) VALUES
(1, 'Vaccines cause autism in children.', 'Chanjo husababisha usonji kwa watoto.', NULL, 'Vaccines do not cause autism. Large studies involving millions of children have found no link. Vaccines protect children from deadly diseases such as measles and polio.', 'Chanjo hazisababishi usonji. Tafiti kubwa zilizohusisha mamilioni ya watoto hazijapata uhusiano wowote. Chanjo huwakinga watoto dhidi ya magonjwa hatari kama surua na polio.', NULL, 'child', '2026-11-23 20:13:01'),
(2, 'Malaria is caused by cold weather or rain.', 'Malaria husababishwa na baridi au mvua.', NULL, 'Malaria is caused by a parasite spread through the bite of an infected mosquito. Rain increases malaria only because it creates standing water where mosquitoes breed.', 'Malaria husababishwa na vimelea vinavyoenezwa kwa kuumwa na mbu aliyeambukizwa. Mvua huongeza malaria kwa sababu tu husababisha maji yaliyotuama ambapo mbu huzaliana.', NULL, 'disease', '2026-11-23 20:13:01'),
(3, 'Colostrum is dirty milk and should be thrown away.', 'Dang\'a ni maziwa machafu na yanapaswa kutupwa.', NULL, 'Colostrum, the first yellow milk, is the most nutritious milk and protects the baby against infections. It should be given to the baby within the first hour after birth.', 'Dang\'a, yaani maziwa ya kwanza ya njano, ndiyo maziwa yenye virutubisho vingi zaidi na humkinga mtoto dhidi ya maambukizi. Mtoto apewe maziwa haya ndani ya saa moja ya kwanza baada ya kuzaliwa.', NULL, 'child', '2026-11-23 20:13:01'),
(4, 'Pregnant women should not eat eggs.', 'Wanawake wajawazito hawapaswi kula mayai.', NULL, 'Eggs are a safe and excellent source of protein during pregnancy. They help the baby grow and keep the mother strong, as long as they are well cooked.', 'Mayai ni chanzo salama na bora cha protini wakati wa ujauzito. Humsaidia mtoto kukua na humpa mama nguvu, ilimradi yamepikwa vizuri.', NULL, 'nutrition', '2026-11-23 20:13:01'),
(5, 'HIV can be spread through mosquito bites.', 'VVU vinaweza kuenezwa kwa kuumwa na mbu.', NULL, 'HIV is not spread by mosquitoes. It is passed through unprotected sex, infected blood, shared needles, and from mother to child.', 'VVU haviambukizwi na mbu. Huambukizwa kupitia ngono isiyo salama, damu yenye maambukizi, kuchangia sindano, na kutoka kwa mama kwenda kwa mtoto.', NULL, 'disease', '2026-11-23 20:13:01'),
(6, 'Traditional herbs can cure HIV.', 'Dawa za mitishamba zinaweza kutibu VVU.', NULL, 'There is no cure for HIV, and herbs cannot cure it. Antiretroviral treatment taken every day controls the virus and allows people to live long, healthy lives.', 'Hakuna tiba ya VVU, na dawa za mitishamba haziwezi kuitibu. Dawa za kufubaza virusi zikitumiwa kila siku hudhibiti virusi na kuwawezesha watu kuishi maisha marefu yenye afya.', NULL, 'disease', '2026-11-23 20:13:01'),
(7, 'Giving birth at home is as safe as giving birth at a health facility.', 'Kujifungulia nyumbani ni salama sawa na kujifungulia katika kituo cha afya.', NULL, 'Delivery at a health facility with a skilled health worker is safest, because complications such as heavy bleeding can be managed quickly. Delivery in public facilities is free in Kenya.', 'Kujifungulia katika kituo cha afya mbele ya mhudumu mwenye ujuzi ndiko salama zaidi, kwa sababu matatizo kama kutokwa na damu nyingi yanaweza kudhibitiwa haraka. Kujifungua katika vituo vya umma ni bure nchini Kenya.', NULL, 'maternal', '2026-11-23 20:13:01'),
(8, 'A child with diarrhoea should not be given water or breast milk.', 'Mtoto anayehara hapaswi kupewa maji wala maziwa ya mama.', NULL, 'A child with diarrhoea needs more fluids, not less. Give ORS after every loose stool, continue breastfeeding and feeding, and give zinc for 10 to 14 days.', 'Mtoto anayehara anahitaji vinywaji zaidi, si kidogo. Mpe ORS baada ya kila choo cha majimaji, endelea kunyonyesha na kumlisha, na umpe zinki kwa siku 10 hadi 14.', NULL, 'child', '2026-11-23 20:13:01'),
(9, 'Tuberculosis is caused by a curse or witchcraft.', 'Kifua kikuu husababishwa na laana au uchawi.', NULL, 'TB is caused by bacteria that spread through the air when a sick person coughs. It is tested and treated free in Kenya and can be cured with six months of medicine.', 'TB husababishwa na bakteria wanaoenea hewani mtu mgonjwa anapokohoa. Kupima na kutibu ni bure nchini Kenya, na inatibika kwa dawa za miezi sita.', NULL, 'disease', '2026-11-23 20:13:01'),
(10, 'Family planning methods cause permanent infertility.', 'Njia za upangaji uzazi husababisha utasa wa kudumu.', NULL, 'Modern family planning methods do not cause permanent infertility. Fertility returns after stopping most methods, though for some injections it may take a few months.', 'Njia za kisasa za upangaji uzazi hazisababishi utasa wa kudumu. Uwezo wa kupata mimba hurudi baada ya kuacha njia nyingi, ingawa kwa baadhi ya sindano unaweza kuchukua miezi michache.', NULL, 'maternal', '2026-11-23 20:13:01'),
(11, 'Cutting a snake bite and sucking out the poison saves lives.', 'Kukata mahali palipoumwa na nyoka na kufyonza sumu huokoa maisha.', NULL, 'Cutting or sucking the bite does not remove venom and can cause more harm. Keep the person calm and still, keep the bitten limb still, and go to a health facility immediately.', 'Kukata au kufyonza jeraha hakuondoi sumu na kunaweza kuleta madhara zaidi. Mtulize mgonjwa na asitikisike, usisogeze kiungo kilichoumwa, na nendeni kituo cha afya mara moja.', NULL, 'other', '2026-11-23 20:13:01'),
(12, 'Raw milk is healthier than boiled milk.', 'Maziwa mabichi yana afya zaidi kuliko maziwa yaliyochemshwa.', NULL, 'Raw milk can carry germs that cause brucellosis, TB and diarrhoea. Boiling kills these germs while keeping milk nutritious, so always boil milk before drinking it.', 'Maziwa mabichi yanaweza kubeba vijidudu vinavyosababisha brusela, kifua kikuu na kuhara. Kuchemsha huua vijidudu hivi huku maziwa yakibaki na virutubisho, kwa hiyo chemsha maziwa kila mara kabla ya kunywa.', NULL, 'nutrition', '2026-11-23 20:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `symptom_guidance`
--

CREATE TABLE `symptom_guidance` (
  `id` int(11) NOT NULL,
  `symptom` varchar(150) NOT NULL,
  `symptom_sw` text DEFAULT NULL,
  `symptom_maa` text DEFAULT NULL,
  `possible_cause` text DEFAULT NULL,
  `possible_cause_sw` text DEFAULT NULL,
  `possible_cause_maa` text DEFAULT NULL,
  `self_care` text DEFAULT NULL,
  `self_care_sw` text DEFAULT NULL,
  `self_care_maa` text DEFAULT NULL,
  `when_to_seek_care` text DEFAULT NULL,
  `when_to_seek_care_sw` text DEFAULT NULL,
  `when_to_seek_care_maa` text DEFAULT NULL,
  `urgency` enum('low','medium','high') NOT NULL DEFAULT 'low',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `symptom_guidance`
--

INSERT INTO `symptom_guidance` (`id`, `symptom`, `symptom_sw`, `symptom_maa`, `possible_cause`, `possible_cause_sw`, `possible_cause_maa`, `self_care`, `self_care_sw`, `self_care_maa`, `when_to_seek_care`, `when_to_seek_care_sw`, `when_to_seek_care_maa`, `urgency`, `created_at`) VALUES
(1, 'Fever', 'Homa', NULL, 'Malaria, flu or other infections such as pneumonia, typhoid or brucellosis.', 'Malaria, mafua au maambukizi mengine kama nimonia, homa ya matumbo au brusela.', NULL, 'Rest, drink plenty of clean water, and cool the body with a damp cloth. Sleep under a treated mosquito net. Get tested for malaria at a health facility.', 'Pumzika, kunywa maji safi ya kutosha, na upoze mwili kwa kitambaa chenye unyevu. Lala ndani ya chandarua chenye dawa. Pima malaria katika kituo cha afya.', NULL, 'Go immediately if the fever is very high, lasts more than two days, comes with fits, a stiff neck, confusion or difficulty breathing, or if the patient is a baby, a young child or a pregnant woman.', 'Nenda mara moja ikiwa homa ni kali sana, inadumu zaidi ya siku mbili, inaambatana na degedege, shingo kukakamaa, kuchanganyikiwa au kupumua kwa shida, au ikiwa mgonjwa ni mtoto mchanga, mtoto mdogo au mama mjamzito.', NULL, 'medium', '2026-11-23 20:13:01'),
(2, 'Diarrhoea', 'Kuhara', NULL, 'Germs in unsafe water or food, poor hygiene, or infections such as cholera.', 'Vijidudu katika maji au chakula kisicho salama, usafi duni, au maambukizi kama kipindupindu.', NULL, 'Drink ORS after every loose stool and keep eating. Children should continue breastfeeding and be given zinc for 10 to 14 days. Wash hands with soap and drink boiled or treated water.', 'Kunywa ORS baada ya kila choo cha majimaji na uendelee kula. Watoto waendelee kunyonya na wapewe zinki kwa siku 10 hadi 14. Osha mikono kwa sabuni na kunywa maji yaliyochemshwa au kutibiwa.', NULL, 'Go immediately if there is blood in the stool, the person cannot drink, keeps vomiting, has sunken eyes, passes little urine, or becomes very weak or sleepy.', 'Nenda mara moja ikiwa kuna damu kwenye choo, mgonjwa hawezi kunywa, anaendelea kutapika, macho yamezama, anakojoa kidogo, au amedhoofika sana au ana usingizi mwingi.', NULL, 'medium', '2026-11-23 20:13:01'),
(3, 'Cough', 'Kikohozi', NULL, 'Common cold or flu, pneumonia, tuberculosis (TB), asthma, or smoke from cooking fires.', 'Homa ya kawaida au mafua, nimonia, kifua kikuu (TB), pumu, au moshi wa kupikia.', NULL, 'Drink warm fluids, rest, and avoid smoke. Cover your mouth when coughing and wash your hands. Keep the house well aired.', 'Kunywa vinywaji vya moto, pumzika, na epuka moshi. Funika mdomo unapokohoa na uoshe mikono. Hakikisha nyumba ina hewa ya kutosha.', NULL, 'Go if the cough lasts two weeks or more, you cough blood, have fever, night sweats or weight loss, or if breathing is fast or difficult, especially in a child.', 'Nenda ikiwa kikohozi kinadumu wiki mbili au zaidi, unakohoa damu, una homa, jasho la usiku au unapungua uzito, au ikiwa kupumua ni kwa haraka au kwa shida, hasa kwa mtoto.', NULL, 'medium', '2026-11-23 20:13:01'),
(4, 'Headache', 'Maumivu ya Kichwa', NULL, 'Tiredness, stress, lack of water or food, malaria, high blood pressure, or eye strain.', 'Uchovu, msongo wa mawazo, ukosefu wa maji au chakula, malaria, shinikizo la juu la damu, au uchovu wa macho.', NULL, 'Rest in a quiet, dark place, drink clean water, and eat regular meals. Paracetamol from a pharmacy may help, following the dose on the packet.', 'Pumzika mahali patulivu na penye giza, kunywa maji safi, na ule milo kwa wakati. Paracetamol kutoka duka la dawa inaweza kusaidia, ukifuata kipimo kilichoandikwa kwenye pakiti.', NULL, 'Go immediately if the headache is sudden and severe, comes with fever and a stiff neck, confusion, blurred vision or weakness, or if a pregnant woman has a severe headache.', 'Nenda mara moja ikiwa maumivu ya kichwa ni ya ghafla na makali, yanaambatana na homa na shingo kukakamaa, kuchanganyikiwa, kuona ukungu au udhaifu, au ikiwa mama mjamzito ana maumivu makali ya kichwa.', NULL, 'low', '2026-11-23 20:13:01'),
(5, 'Chest Pain', 'Maumivu ya Kifua', NULL, 'Heart problems, pneumonia, tuberculosis, muscle strain, or heartburn.', 'Matatizo ya moyo, nimonia, kifua kikuu, kuvutika kwa misuli, au kiungulia.', NULL, 'Stop all activity, sit down and rest, and stay calm. Do not walk long distances alone.', 'Acha shughuli zote, keti na upumzike, na utulie. Usitembee umbali mrefu peke yako.', NULL, 'Go immediately, as this can be an emergency, especially if the pain is crushing or tight, spreads to the arm, jaw or back, or comes with sweating, shortness of breath or fainting.', 'Nenda mara moja kwa kuwa hii inaweza kuwa dharura, hasa ikiwa maumivu ni ya kubana, yanaenea hadi mkononi, tayani au mgongoni, au yanaambatana na jasho, kukosa pumzi au kuzirai.', NULL, 'high', '2026-11-23 20:13:01'),
(6, 'Difficulty Breathing', 'Kupumua kwa Shida', NULL, 'Pneumonia, asthma, tuberculosis, heart problems, or a severe allergic reaction.', 'Nimonia, pumu, kifua kikuu, matatizo ya moyo, au mzio mkali.', NULL, 'Sit upright, loosen tight clothing, and move away from smoke and dust. If you have an asthma inhaler, use it as prescribed.', 'Keti wima, legeza nguo zinazobana, na ukae mbali na moshi na vumbi. Ikiwa una dawa ya kuvuta ya pumu, itumie kama ulivyoelekezwa.', NULL, 'Go immediately if breathing is fast or difficult, the chest pulls in when breathing, the lips turn blue, or the person cannot speak or drink. In children, fast breathing is a danger sign.', 'Nenda mara moja ikiwa kupumua ni kwa haraka au kwa shida, kifua kinavutika ndani wakati wa kupumua, midomo inabadilika kuwa ya bluu, au mgonjwa hawezi kuongea wala kunywa. Kwa watoto, kupumua haraka ni dalili ya hatari.', NULL, 'high', '2026-11-23 20:13:01'),
(7, 'Vomiting', 'Kutapika', NULL, 'Stomach infections, food poisoning, malaria, early pregnancy, or other illnesses.', 'Maambukizi ya tumbo, sumu ya chakula, malaria, ujauzito wa mapema, au magonjwa mengine.', NULL, 'Take small sips of clean water or ORS often. Rest, and eat light food when you feel able. Avoid oily food.', 'Kunywa maji safi au ORS kidogo kidogo mara kwa mara. Pumzika, na ule chakula chepesi unapoweza. Epuka vyakula vyenye mafuta.', NULL, 'Go immediately if the vomiting does not stop, there is blood or green fluid in the vomit, the person cannot keep any fluids down, shows signs of dehydration, or has severe abdominal pain.', 'Nenda mara moja ikiwa kutapika hakukomi, kuna damu au majimaji ya kijani kwenye matapishi, mgonjwa hawezi kubakiza kinywaji chochote, ana dalili za kupungukiwa na maji, au ana maumivu makali ya tumbo.', NULL, 'medium', '2026-11-23 20:13:01'),
(8, 'Abdominal Pain', 'Maumivu ya Tumbo', NULL, 'Stomach infections, worms, ulcers, constipation, urinary infections, or problems in pregnancy.', 'Maambukizi ya tumbo, minyoo, vidonda vya tumbo, kufunga choo, maambukizi ya njia ya mkojo, au matatizo ya ujauzito.', NULL, 'Rest, drink clean water, and eat light meals. Avoid spicy and oily food.', 'Pumzika, kunywa maji safi, na ule milo myepesi. Epuka vyakula vyenye pilipili na mafuta.', NULL, 'Go immediately if the pain is severe or does not go away, the stomach is hard or swollen, there is blood in the stool or vomit, there is a high fever, or a pregnant woman has abdominal pain or bleeding.', 'Nenda mara moja ikiwa maumivu ni makali au hayaishi, tumbo ni gumu au limevimba, kuna damu kwenye choo au matapishi, kuna homa kali, au mama mjamzito ana maumivu ya tumbo au anatokwa na damu.', NULL, 'medium', '2026-11-23 20:13:01'),
(9, 'Swollen Legs', 'Kuvimba Miguu', NULL, 'Standing for long periods, late pregnancy, high blood pressure, heart, kidney or liver problems, or a blood clot.', 'Kusimama kwa muda mrefu, ujauzito wa miezi ya mwisho, shinikizo la juu la damu, matatizo ya moyo, figo au ini, au damu kuganda.', NULL, 'Raise the legs when resting, reduce salt in food, and avoid standing for long periods.', 'Inua miguu unapopumzika, punguza chumvi kwenye chakula, na epuka kusimama kwa muda mrefu.', NULL, 'Go immediately if only one leg is swollen, red and painful, if there is shortness of breath, or if a pregnant woman also has swelling of the face and hands, a severe headache or blurred vision.', 'Nenda mara moja ikiwa mguu mmoja tu umevimba, ni mwekundu na unauma, ikiwa kuna kukosa pumzi, au ikiwa mama mjamzito pia amevimba uso na mikono, ana maumivu makali ya kichwa au anaona ukungu.', NULL, 'medium', '2026-11-23 20:13:01'),
(10, 'Skin Rash', 'Upele wa Ngozi', NULL, 'Allergies, insect bites, fungal infections, scabies, or infections such as measles or chickenpox.', 'Mzio, kuumwa na wadudu, maambukizi ya fangasi, upele wa kuambukiza, au maambukizi kama surua au tetekuwanga.', NULL, 'Keep the skin clean and dry, avoid scratching, and wear clean, loose clothing. Wash bedding and clothes regularly.', 'Weka ngozi ikiwa safi na kavu, epuka kujikuna, na vaa nguo safi zisizobana. Fua mashuka na nguo mara kwa mara.', NULL, 'Go if the rash spreads quickly, comes with fever, blisters or swelling of the face or lips, if there is difficulty breathing, or if a child has a rash with fever, which may be measles.', 'Nenda ikiwa upele unaenea haraka, unaambatana na homa, malengelenge au uvimbe wa uso au midomo, ikiwa kuna kupumua kwa shida, au ikiwa mtoto ana upele pamoja na homa, ambayo inaweza kuwa surua.', NULL, 'low', '2026-11-23 20:13:01'),
(11, 'Excessive Thirst and Frequent Urination', 'Kiu Kingi na Kukojoa Mara kwa Mara', NULL, 'Diabetes (high blood sugar), urinary tract infection, or not drinking enough water in hot weather.', 'Kisukari (sukari nyingi mwilini), maambukizi ya njia ya mkojo, au kutokunywa maji ya kutosha wakati wa joto.', NULL, 'Drink clean water, and limit sugary drinks and sweets. Note how often you urinate and whether you are losing weight.', 'Kunywa maji safi, na upunguze vinywaji vyenye sukari na peremende. Angalia ni mara ngapi unakojoa na kama unapungua uzito.', NULL, 'Go to a health facility for a blood sugar test if thirst and urination continue, especially with weight loss, tiredness or blurred vision. Go immediately if there is confusion, vomiting or fast breathing.', 'Nenda kituo cha afya kupima sukari ikiwa kiu na kukojoa kunaendelea, hasa pamoja na kupungua uzito, uchovu au kuona ukungu. Nenda mara moja ikiwa kuna kuchanganyikiwa, kutapika au kupumua haraka.', NULL, 'medium', '2026-11-23 20:13:01'),
(12, 'Sadness and Low Mood', 'Huzuni na Hali ya Chini ya Moyo', NULL, 'Stress, loss of a loved one, life difficulties, depression, or depression after childbirth.', 'Msongo wa mawazo, kufiwa na mpendwa, matatizo ya maisha, sonona, au sonona baada ya kujifungua.', NULL, 'Talk to someone you trust, such as a family member, friend, religious leader or community health worker. Try to keep a routine, eat regularly and get enough sleep.', 'Zungumza na mtu unayemwamini, kama mwanafamilia, rafiki, kiongozi wa dini au mhudumu wa afya wa jamii. Jaribu kufuata ratiba, kula kwa wakati na kupata usingizi wa kutosha.', NULL, 'Seek help at a health facility if the sadness lasts more than two weeks or makes daily life hard. If you have thoughts of harming yourself, seek help immediately.', 'Tafuta msaada katika kituo cha afya ikiwa huzuni inadumu zaidi ya wiki mbili au inafanya maisha ya kila siku kuwa magumu. Ukiwa na mawazo ya kujidhuru, tafuta msaada mara moja.', NULL, 'medium', '2026-11-23 20:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `symptom_submissions`
--

CREATE TABLE `symptom_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symptom_input` text NOT NULL,
  `ml_result` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `preferred_language` enum('english','kiswahili','maa') NOT NULL DEFAULT 'english',
  `password` varchar(255) NOT NULL,
  `role` enum('community_member','chw','admin') NOT NULL DEFAULT 'community_member',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `preferred_language`, `password`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin@samburuhealth.local', '0700000001', 'english', '$2y$10$YQ30mGbohj9Gbot7akIrG.JM7NDgsrHRPuTn7b60A35WqOLnq9p76', 'admin', '2026-11-23 20:13:01'),
(2, 'Demo Health Worker', 'chw@samburuhealth.local', '0700000002', 'kiswahili', '$2y$10$DJ0ZwX.msHXjFgsNk1gp.uRAQJF87i9cFwk9nL25Nk6eKFkOtybrK', 'chw', '2026-11-23 20:13:01'),
(3, 'Demo Community Member', 'member@samburuhealth.local', '0700000003', 'english', '$2y$10$3t43hXU94Tce6KQkyKX86eEiHDiOXcBvtomLTNuC9GMi5U3J/344S', 'community_member', '2026-11-23 20:13:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type_date` (`activity_type`,`created_at`);

--
-- Indexes for table `chw_notes`
--
ALTER TABLE `chw_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chw_id` (`chw_id`),
  ADD KEY `idx_patient_id` (`patient_id`);

--
-- Indexes for table `content_suggestions`
--
ALTER TABLE `content_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cs_status` (`status`),
  ADD KEY `fk_cs_user` (`user_id`);

--
-- Indexes for table `health_education`
--
ALTER TABLE `health_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_he_category` (`category`);

--
-- Indexes for table `myth_facts`
--
ALTER TABLE `myth_facts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mf_category` (`category`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pr_token` (`token`),
  ADD KEY `fk_pr_user` (`user_id`);

--
-- Indexes for table `symptom_guidance`
--
ALTER TABLE `symptom_guidance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `symptom_submissions`
--
ALTER TABLE `symptom_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ss_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chw_notes`
--
ALTER TABLE `chw_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_suggestions`
--
ALTER TABLE `content_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_education`
--
ALTER TABLE `health_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `myth_facts`
--
ALTER TABLE `myth_facts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `symptom_guidance`
--
ALTER TABLE `symptom_guidance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `symptom_submissions`
--
ALTER TABLE `symptom_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chw_notes`
--
ALTER TABLE `chw_notes`
  ADD CONSTRAINT `fk_chw_notes_chw` FOREIGN KEY (`chw_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chw_notes_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_suggestions`
--
ALTER TABLE `content_suggestions`
  ADD CONSTRAINT `fk_cs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `symptom_submissions`
--
ALTER TABLE `symptom_submissions`
  ADD CONSTRAINT `fk_ss_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

--
-- Dumping data for table `pma__export_templates`
--

INSERT INTO `pma__export_templates` (`id`, `username`, `export_type`, `template_name`, `template_data`) VALUES
(1, 'root', 'server', 'multilingual_health_platform', '{\"quick_or_custom\":\"quick\",\"what\":\"sql\",\"db_select[]\":[\"amarahotel\",\"phpmyadmin\",\"restaurant\",\"test\",\"university\",\"wellness_app_db\"],\"aliases_new\":\"\",\"output_format\":\"sendit\",\"filename_template\":\"@SERVER@\",\"remember_template\":\"on\",\"charset\":\"utf-8\",\"compression\":\"none\",\"maxsize\":\"\",\"codegen_structure_or_data\":\"data\",\"codegen_format\":\"0\",\"csv_separator\":\",\",\"csv_enclosed\":\"\\\"\",\"csv_escaped\":\"\\\"\",\"csv_terminated\":\"AUTO\",\"csv_null\":\"NULL\",\"csv_columns\":\"something\",\"csv_structure_or_data\":\"data\",\"excel_null\":\"NULL\",\"excel_columns\":\"something\",\"excel_edition\":\"win\",\"excel_structure_or_data\":\"data\",\"json_structure_or_data\":\"data\",\"json_unicode\":\"something\",\"latex_caption\":\"something\",\"latex_structure_or_data\":\"structure_and_data\",\"latex_structure_caption\":\"Structure of table @TABLE@\",\"latex_structure_continued_caption\":\"Structure of table @TABLE@ (continued)\",\"latex_structure_label\":\"tab:@TABLE@-structure\",\"latex_relation\":\"something\",\"latex_comments\":\"something\",\"latex_mime\":\"something\",\"latex_columns\":\"something\",\"latex_data_caption\":\"Content of table @TABLE@\",\"latex_data_continued_caption\":\"Content of table @TABLE@ (continued)\",\"latex_data_label\":\"tab:@TABLE@-data\",\"latex_null\":\"\\\\textit{NULL}\",\"mediawiki_structure_or_data\":\"data\",\"mediawiki_caption\":\"something\",\"mediawiki_headers\":\"something\",\"htmlword_structure_or_data\":\"structure_and_data\",\"htmlword_null\":\"NULL\",\"ods_null\":\"NULL\",\"ods_structure_or_data\":\"data\",\"odt_structure_or_data\":\"structure_and_data\",\"odt_relation\":\"something\",\"odt_comments\":\"something\",\"odt_mime\":\"something\",\"odt_columns\":\"something\",\"odt_null\":\"NULL\",\"pdf_report_title\":\"\",\"pdf_structure_or_data\":\"data\",\"phparray_structure_or_data\":\"data\",\"sql_include_comments\":\"something\",\"sql_header_comment\":\"\",\"sql_use_transaction\":\"something\",\"sql_compatibility\":\"NONE\",\"sql_structure_or_data\":\"structure_and_data\",\"sql_create_table\":\"something\",\"sql_auto_increment\":\"something\",\"sql_create_view\":\"something\",\"sql_create_trigger\":\"something\",\"sql_backquotes\":\"something\",\"sql_type\":\"INSERT\",\"sql_insert_syntax\":\"both\",\"sql_max_query_size\":\"50000\",\"sql_hex_for_binary\":\"something\",\"sql_utc_time\":\"something\",\"texytext_structure_or_data\":\"structure_and_data\",\"texytext_null\":\"NULL\",\"yaml_structure_or_data\":\"data\",\"\":null,\"as_separate_files\":null,\"csv_removeCRLF\":null,\"excel_removeCRLF\":null,\"json_pretty_print\":null,\"htmlword_columns\":null,\"ods_columns\":null,\"sql_dates\":null,\"sql_relation\":null,\"sql_mime\":null,\"sql_disable_fk\":null,\"sql_views_as_tables\":null,\"sql_metadata\":null,\"sql_drop_database\":null,\"sql_drop_table\":null,\"sql_if_not_exists\":null,\"sql_simple_view_export\":null,\"sql_view_current_user\":null,\"sql_or_replace_view\":null,\"sql_procedure_function\":null,\"sql_truncate\":null,\"sql_delayed\":null,\"sql_ignore\":null,\"texytext_columns\":null}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"wellness_app_db\",\"table\":\"users\"},{\"db\":\"wellness_app_db\",\"table\":\"subscriptions_subscription\"},{\"db\":\"wellness_app_db\",\"table\":\"moodtracker\"},{\"db\":\"wellness_app_db\",\"table\":\"journal_entries\"},{\"db\":\"wellness_app_db\",\"table\":\"book_appointment\"},{\"db\":\"wellness_app_db\",\"table\":\"authtoken_token\"},{\"db\":\"information_schema\",\"table\":\"KEY_COLUMN_USAGE\"},{\"db\":\"amarahotel\",\"table\":\"login\"},{\"db\":\"wellness_app_db\",\"table\":\"patients\"},{\"db\":\"wellness_app_db\",\"table\":\"subscriptions\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

--
-- Dumping data for table `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'amarahotel', 'registration', '[]', '2024-07-02 08:21:29'),
('root', 'restaurant', 'cafeteria', '{\"sorted_col\":\"`cafe_waitingcard` ASC\"}', '2023-12-02 18:08:57'),
('root', 'university', 'students', '{\"sorted_col\":\"`students`.`student_id` ASC\"}', '2023-11-29 22:44:39'),
('root', 'wellness_app_db', 'moodtracker', '{\"CREATE_TIME\":\"2025-07-05 10:48:02\"}', '2025-07-13 13:53:53'),
('root', 'wellness_app_db', 'users', '[]', '2025-07-13 09:43:37');

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-11-23 17:13:02', '{\"Console\\/Mode\":\"collapse\",\"NavigationWidth\":0,\"lang\":\"en_GB\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `restaurant`
--
CREATE DATABASE IF NOT EXISTS `restaurant` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `restaurant`;

-- --------------------------------------------------------

--
-- Table structure for table `cafeteria`
--

CREATE TABLE `cafeteria` (
  `cafe_name` varchar(20) NOT NULL,
  `cafe_menu` varchar(20) NOT NULL,
  `cafe_waitingcard` varchar(20) NOT NULL,
  `cafe_serversname` varchar(15) NOT NULL,
  `cafe_owner` varchar(20) NOT NULL,
  `cafe_location` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cafeteria`
--

INSERT INTO `cafeteria` (`cafe_name`, `cafe_menu`, `cafe_waitingcard`, `cafe_serversname`, `cafe_owner`, `cafe_location`) VALUES
('Amazon', 'Shawarma', 'five', 'Steve', 'Cynthia', 'Nairobi'),
('Amazon', 'Chips', 'four', 'Klaus', 'Cynthia', 'Nairobi'),
('Amazon', 'Meatpie', 'one', 'Stacy', 'Cynthia', 'Nairobi'),
('Amazon', 'Tea', 'seven', 'Lynn', 'Cynthia', 'Nairobi'),
('Amazon', 'Black coffee', 'six', 'Milly', 'Cynthia', 'Nairobi'),
('Amazon', 'Pizza', 'three', 'Andrew', 'Cynthia', 'Nairobi'),
('Amazon', 'Chicken wings', 'two', 'Havisa', 'Cynthia', 'Nairobi');

-- --------------------------------------------------------

--
-- Table structure for table `logindatabase`
--

CREATE TABLE `logindatabase` (
  `username` varchar(20) NOT NULL,
  `password` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logindatabase`
--

INSERT INTO `logindatabase` (`username`, `password`) VALUES
('Andrew', '12345'),
('Cynthia', '12345'),
('Havisa', '12345'),
('Klaus', '12345'),
('Lynn', '12345'),
('Milly', '12345'),
('Stacy', '12345'),
('Steve', '12345');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cafeteria`
--
ALTER TABLE `cafeteria`
  ADD PRIMARY KEY (`cafe_waitingcard`);

--
-- Indexes for table `logindatabase`
--
ALTER TABLE `logindatabase`
  ADD PRIMARY KEY (`username`);
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
--
-- Database: `university`
--
CREATE DATABASE IF NOT EXISTS `university` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `university`;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(6) NOT NULL,
  `student_name` varchar(40) NOT NULL,
  `student_course` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_name`, `student_course`) VALUES
(0, '[Cynthia]', '[BBIT]'),
(155154, 'Cynthia', 'BBIT');
--
-- Database: `wellness_app_db`
--
CREATE DATABASE IF NOT EXISTS `wellness_app_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `wellness_app_db`;

-- --------------------------------------------------------

--
-- Table structure for table `authtoken_token`
--

CREATE TABLE `authtoken_token` (
  `key` varchar(40) NOT NULL,
  `created` datetime(6) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_group`
--

CREATE TABLE `auth_group` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_group_permissions`
--

CREATE TABLE `auth_group_permissions` (
  `id` bigint(20) NOT NULL,
  `group_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_permission`
--

CREATE TABLE `auth_permission` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content_type_id` int(11) NOT NULL,
  `codename` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_permission`
--

INSERT INTO `auth_permission` (`id`, `name`, `content_type_id`, `codename`) VALUES
(1, 'Can add log entry', 1, 'add_logentry'),
(2, 'Can change log entry', 1, 'change_logentry'),
(3, 'Can delete log entry', 1, 'delete_logentry'),
(4, 'Can view log entry', 1, 'view_logentry'),
(5, 'Can add permission', 2, 'add_permission'),
(6, 'Can change permission', 2, 'change_permission'),
(7, 'Can delete permission', 2, 'delete_permission'),
(8, 'Can view permission', 2, 'view_permission'),
(9, 'Can add group', 3, 'add_group'),
(10, 'Can change group', 3, 'change_group'),
(11, 'Can delete group', 3, 'delete_group'),
(12, 'Can view group', 3, 'view_group'),
(13, 'Can add user', 4, 'add_user'),
(14, 'Can change user', 4, 'change_user'),
(15, 'Can delete user', 4, 'delete_user'),
(16, 'Can view user', 4, 'view_user'),
(17, 'Can add content type', 5, 'add_contenttype'),
(18, 'Can change content type', 5, 'change_contenttype'),
(19, 'Can delete content type', 5, 'delete_contenttype'),
(20, 'Can view content type', 5, 'view_contenttype'),
(21, 'Can add session', 6, 'add_session'),
(22, 'Can change session', 6, 'change_session'),
(23, 'Can delete session', 6, 'delete_session'),
(24, 'Can view session', 6, 'view_session'),
(25, 'Can add Token', 7, 'add_token'),
(26, 'Can change Token', 7, 'change_token'),
(27, 'Can delete Token', 7, 'delete_token'),
(28, 'Can view Token', 7, 'view_token'),
(29, 'Can add Token', 8, 'add_tokenproxy'),
(30, 'Can change Token', 8, 'change_tokenproxy'),
(31, 'Can delete Token', 8, 'delete_tokenproxy'),
(32, 'Can view Token', 8, 'view_tokenproxy'),
(33, 'Can add subscription', 9, 'add_subscription'),
(34, 'Can change subscription', 9, 'change_subscription'),
(35, 'Can delete subscription', 9, 'delete_subscription'),
(36, 'Can view subscription', 9, 'view_subscription');

-- --------------------------------------------------------

--
-- Table structure for table `auth_user`
--

CREATE TABLE `auth_user` (
  `id` int(11) NOT NULL,
  `password` varchar(128) NOT NULL,
  `last_login` datetime(6) DEFAULT NULL,
  `is_superuser` tinyint(1) NOT NULL,
  `username` varchar(150) NOT NULL,
  `first_name` varchar(150) NOT NULL,
  `last_name` varchar(150) NOT NULL,
  `email` varchar(254) NOT NULL,
  `is_staff` tinyint(1) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `date_joined` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_user_groups`
--

CREATE TABLE `auth_user_groups` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_user_user_permissions`
--

CREATE TABLE `auth_user_user_permissions` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book_appointment`
--

CREATE TABLE `book_appointment` (
  `entry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_appointment`
--

INSERT INTO `book_appointment` (`entry_id`, `user_id`, `fullname`, `email`, `phone`, `appointment_date`, `appointment_time`, `notes`, `created_at`, `status`) VALUES
(16, 22169417, 'Cynthia', 'ck697315@gmail.com', '0112030924', '2025-07-14', '10:30 AM', 'Not really.', '2025-07-13 16:47:00', 'Approved'),
(18, 22169417, 'Cynthia', 'ck697315@gmail.com', '254112030924', '2025-07-14', '10:30 AM', 'Not really', '2025-07-14 06:06:04', 'Pending'),
(19, 330440, 'Terryanne Sanya', 'ck697315@gmail.com', '0112030924', '2025-07-15', '10:30 AM', 'No not really', '2025-07-14 08:16:08', 'Pending'),
(20, 22169417, 'Cynthia', 'ck697315@gmail.com', '0112030924', '2026-03-27', '10:30 AM', 'GYTTFTYIUGGFFTFKGHIJO;K\'', '2026-03-26 09:42:02', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `django_admin_log`
--

CREATE TABLE `django_admin_log` (
  `id` int(11) NOT NULL,
  `action_time` datetime(6) NOT NULL,
  `object_id` longtext DEFAULT NULL,
  `object_repr` varchar(200) NOT NULL,
  `action_flag` smallint(5) UNSIGNED NOT NULL CHECK (`action_flag` >= 0),
  `change_message` longtext NOT NULL,
  `content_type_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `django_content_type`
--

CREATE TABLE `django_content_type` (
  `id` int(11) NOT NULL,
  `app_label` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `django_content_type`
--

INSERT INTO `django_content_type` (`id`, `app_label`, `model`) VALUES
(1, 'admin', 'logentry'),
(3, 'auth', 'group'),
(2, 'auth', 'permission'),
(4, 'auth', 'user'),
(7, 'authtoken', 'token'),
(8, 'authtoken', 'tokenproxy'),
(5, 'contenttypes', 'contenttype'),
(6, 'sessions', 'session'),
(9, 'subscriptions', 'subscription');

-- --------------------------------------------------------

--
-- Table structure for table `django_migrations`
--

CREATE TABLE `django_migrations` (
  `id` bigint(20) NOT NULL,
  `app` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `applied` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `django_migrations`
--

INSERT INTO `django_migrations` (`id`, `app`, `name`, `applied`) VALUES
(1, 'contenttypes', '0001_initial', '2025-07-10 21:08:09.945436'),
(2, 'auth', '0001_initial', '2025-07-10 21:08:10.310315'),
(3, 'admin', '0001_initial', '2025-07-10 21:08:10.394457'),
(4, 'admin', '0002_logentry_remove_auto_add', '2025-07-10 21:08:10.399567'),
(5, 'admin', '0003_logentry_add_action_flag_choices', '2025-07-10 21:08:10.405043'),
(6, 'contenttypes', '0002_remove_content_type_name', '2025-07-10 21:08:10.452538'),
(7, 'auth', '0002_alter_permission_name_max_length', '2025-07-10 21:08:10.490992'),
(8, 'auth', '0003_alter_user_email_max_length', '2025-07-10 21:08:10.503759'),
(9, 'auth', '0004_alter_user_username_opts', '2025-07-10 21:08:10.510379'),
(10, 'auth', '0005_alter_user_last_login_null', '2025-07-10 21:08:10.547824'),
(11, 'auth', '0006_require_contenttypes_0002', '2025-07-10 21:08:10.550545'),
(12, 'auth', '0007_alter_validators_add_error_messages', '2025-07-10 21:08:10.555757'),
(13, 'auth', '0008_alter_user_username_max_length', '2025-07-10 21:08:10.566115'),
(14, 'auth', '0009_alter_user_last_name_max_length', '2025-07-10 21:08:10.577411'),
(15, 'auth', '0010_alter_group_name_max_length', '2025-07-10 21:08:10.595210'),
(16, 'auth', '0011_update_proxy_permissions', '2025-07-10 21:08:10.602244'),
(17, 'auth', '0012_alter_user_first_name_max_length', '2025-07-10 21:08:10.611543'),
(18, 'authtoken', '0001_initial', '2025-07-10 21:08:10.681463'),
(19, 'authtoken', '0002_auto_20160226_1747', '2025-07-10 21:08:10.695415'),
(20, 'authtoken', '0003_tokenproxy', '2025-07-10 21:08:10.697993'),
(21, 'authtoken', '0004_alter_tokenproxy_options', '2025-07-10 21:08:10.702063'),
(22, 'sessions', '0001_initial', '2025-07-10 21:08:10.727368'),
(23, 'subscriptions', '0001_initial', '2025-07-10 21:08:10.774045'),
(24, 'subscriptions', '0002_subscription_plan_and_more', '2025-07-10 21:08:10.864748'),
(25, 'subscriptions', '0003_alter_subscription_checkout_request_id_and_more', '2025-07-13 18:41:07.223067');

-- --------------------------------------------------------

--
-- Table structure for table `django_session`
--

CREATE TABLE `django_session` (
  `session_key` varchar(40) NOT NULL,
  `session_data` longtext NOT NULL,
  `expire_date` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `entry_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `entry` text NOT NULL,
  `date_logged` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`entry_id`, `user_id`, `entry`, `date_logged`) VALUES
(10, '22169417', 'Today was a bit hard, trying to figure out why my baby was crying, but i got enough support and I\'m grateful for that', '2025-07-13 19:45:40'),
(11, '990880', 'Today has been a pretty slow day. I am so glad and grateful for my family and friends, and I couldn\'t ask for more.', '2025-07-13 21:57:21'),
(12, '22169417', 'Today was a very good day, I felt supported and I got a lot of help from my family and friends.', '2025-07-14 09:00:14'),
(13, '330440', 'Today was a goo day, I hope that tomorrow will be better', '2025-07-14 11:14:41'),
(14, '22169417', 'BLAH BLAH BLAH', '2026-03-26 12:40:59');

-- --------------------------------------------------------

--
-- Table structure for table `moodtracker`
--

CREATE TABLE `moodtracker` (
  `entry_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `mood` varchar(100) NOT NULL,
  `influencing_factors` text NOT NULL,
  `notes` text NOT NULL,
  `date_logged` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moodtracker`
--

INSERT INTO `moodtracker` (`entry_id`, `user_id`, `mood`, `influencing_factors`, `notes`, `date_logged`) VALUES
(25, '22169417', 'Sad', 'Feeding Challenges', 'I\'m having trouble getting enough milk', '2025-07-13 19:37:17'),
(26, '990880', 'Anxious', 'Hormonal Changes, Physical Discomfort', 'Today I feel a bit insecure, not too comfortable with my body, but at least I have a good support system.', '2025-07-13 21:56:07'),
(27, '22169417', 'Excited', 'Social Support', 'I have been getting a lot of support from my family and friends, so that is something to be grateful today.', '2025-07-14 08:58:51'),
(28, '330440', 'Anxious', 'Hormonal Changes, Family Stress', 'I have been feeling a bit overwhelmed but i\'m grateful my family and friends are there', '2025-07-14 11:14:04'),
(29, '22169417', 'Angry', 'Physical Discomfort', 'Sperm production', '2026-03-26 12:40:27');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions_subscription`
--

CREATE TABLE `subscriptions_subscription` (
  `id` bigint(20) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `mpesa_receipt_number` varchar(100) DEFAULT NULL,
  `checkout_request_id` varchar(100) DEFAULT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `transaction_date` datetime(6) DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions_subscription`
--

INSERT INTO `subscriptions_subscription` (`id`, `phone_number`, `amount`, `mpesa_receipt_number`, `checkout_request_id`, `merchant_request_id`, `transaction_date`, `payment_status`, `created_at`, `user_id`, `plan`) VALUES
(1, '254712345678', 1000.00, '', 'test-checkout-id', 'test-merchant-id', '2025-07-13 18:48:06.522078', 'Pending', '2025-07-13 18:48:06.551078', NULL, 'basic'),
(2, '254712345678', 1000.00, '', 'ws_CO_14072025071433716712345678', '659f-4a49-a1e5-91c821fcf09e2004', '2025-07-14 04:12:09.905250', 'Pending', '2025-07-14 04:12:09.913109', NULL, 'basic'),
(3, '254712345678', 1000.00, '', 'ws_CO_14072025072451450712345678', '4627-4c44-bb14-72652c4d7a773581', '2025-07-14 04:22:27.598079', 'Pending', '2025-07-14 04:22:27.615918', NULL, 'basic'),
(4, '254112030924', 1000.00, '', 'ws_CO_14072025072821482112030924', '659f-4a49-a1e5-91c821fcf09e2158', '2025-07-14 04:25:57.628816', 'Pending', '2025-07-14 04:25:57.645332', NULL, 'basic'),
(5, '254720493743', 1000.00, '', 'ws_CO_14072025073102582720493743', '93e5-42dc-be13-63e0faeb14373623', '2025-07-14 04:28:39.223339', 'Pending', '2025-07-14 04:28:39.240237', NULL, 'basic'),
(6, '254112030924', 1000.00, '', 'ws_CO_14072025090629658112030924', '435b-4a8c-8d6e-843c3fd099523162', '2025-07-14 06:04:06.115290', 'Pending', '2025-07-14 06:04:06.149490', NULL, 'basic'),
(7, '254112030924', 1000.00, '', 'ws_CO_14072025111727013112030924', '4627-4c44-bb14-72652c4d7a776542', '2025-07-14 08:15:03.437379', 'Pending', '2025-07-14 08:15:03.461411', NULL, 'basic');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `entry_id` int(11) NOT NULL,
  `id` varchar(100) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(200) NOT NULL,
  `role` enum('postpartum mother','therapist','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`entry_id`, `id`, `fullname`, `email`, `password`, `role`, `created_at`, `reset_token`, `token_expiry`) VALUES
(24, '22169417', 'Cynthia', 'ck697315@gmail.com', '$2y$10$fAzlIFZroKhnA6QyaO982OpqAOVxyZ37Y20TvRVGtUBBZ3sR4sgyy', 'postpartum mother', '2025-07-13 15:28:44', NULL, NULL),
(25, '990880', 'Esther', 'esther@gmail.com', '$2y$10$G96kFG9AORdAFOxHqjPsneIjyqSzDo4fbk.MgxJUbQL8hfmnbf7HG', 'postpartum mother', '2025-07-13 17:54:34', NULL, NULL),
(26, '554334', 'Iris', 'iris@gmail.com', '$2y$10$rRgei/ZfQZxkUHLT7W00tORBnb6/Jkz7y8X/Zw0Xkli41kOuUFLjm', 'therapist', '2025-07-13 18:44:57', NULL, NULL),
(27, '776667', 'Caitlin', 'caitlin@gmail.com', '$2y$10$PpxWD.yo3hAp2ZLZnkVYMewp004FU.mbBUhuG4xQTCOoxffROsLsm', 'admin', '2025-07-13 19:56:01', NULL, NULL),
(28, '330440', 'Terryanne', 'terryanne@gmail.com', '$2y$10$d4DNw8FGiJc3M.9tGKKtUeyZiCS2rZvukbLdJ6Ql3VVROB1LnDDtG', 'postpartum mother', '2025-07-14 07:12:40', NULL, NULL),
(29, '667909', 'Amaya', 'amaya@gmail.com', '$2y$10$B5j2ZQsorRyn9vKAUpppUu36c0O8DlCkpBf.wiIvOgeXfCHd/Nh4W', 'therapist', '2025-07-14 07:16:44', NULL, NULL),
(30, '44567', 'Chris', 'chris@gmail.com', '$2y$10$hbCEMxHfR8pM4t/1ANllfuULZonF1tOfQTaiwFNje8ajEhIjLAfrq', 'admin', '2025-07-14 07:18:24', NULL, NULL),
(31, '546790', 'Lu lu', 'aishwnwfkyrlbmuvrj@xfavaj.com', '$2y$10$bAruoqPL9N4FWYI6.m7a0u5ZomTpZZlh9Q/SfFCbmDcJbtai7Itwe', 'therapist', '2026-03-26 07:47:04', NULL, NULL),
(32, '123456', 'Lu lu', 'medow21805@exahut.com', '$2y$10$adsBqydIca/HPFaQ3kCxEuVFpASL6V5b3lU7asQP673UihZiQj3vC', 'admin', '2026-03-26 07:56:07', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authtoken_token`
--
ALTER TABLE `authtoken_token`
  ADD PRIMARY KEY (`key`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `auth_group`
--
ALTER TABLE `auth_group`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `auth_group_permissions`
--
ALTER TABLE `auth_group_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auth_group_permissions_group_id_permission_id_0cd325b0_uniq` (`group_id`,`permission_id`),
  ADD KEY `auth_group_permissio_permission_id_84c5c92e_fk_auth_perm` (`permission_id`);

--
-- Indexes for table `auth_permission`
--
ALTER TABLE `auth_permission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auth_permission_content_type_id_codename_01ab375a_uniq` (`content_type_id`,`codename`);

--
-- Indexes for table `auth_user`
--
ALTER TABLE `auth_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `auth_user_groups`
--
ALTER TABLE `auth_user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auth_user_groups_user_id_group_id_94350c0c_uniq` (`user_id`,`group_id`),
  ADD KEY `auth_user_groups_group_id_97559544_fk_auth_group_id` (`group_id`);

--
-- Indexes for table `auth_user_user_permissions`
--
ALTER TABLE `auth_user_user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auth_user_user_permissions_user_id_permission_id_14a6b632_uniq` (`user_id`,`permission_id`),
  ADD KEY `auth_user_user_permi_permission_id_1fbb5f2c_fk_auth_perm` (`permission_id`);

--
-- Indexes for table `book_appointment`
--
ALTER TABLE `book_appointment`
  ADD PRIMARY KEY (`entry_id`);

--
-- Indexes for table `django_admin_log`
--
ALTER TABLE `django_admin_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `django_admin_log_content_type_id_c4bce8eb_fk_django_co` (`content_type_id`),
  ADD KEY `django_admin_log_user_id_c564eba6_fk_auth_user_id` (`user_id`);

--
-- Indexes for table `django_content_type`
--
ALTER TABLE `django_content_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `django_content_type_app_label_model_76bd3d3b_uniq` (`app_label`,`model`);

--
-- Indexes for table `django_migrations`
--
ALTER TABLE `django_migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `django_session`
--
ALTER TABLE `django_session`
  ADD PRIMARY KEY (`session_key`),
  ADD KEY `django_session_expire_date_a5c62663` (`expire_date`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `journal_entries_ibfk_1` (`user_id`);

--
-- Indexes for table `moodtracker`
--
ALTER TABLE `moodtracker`
  ADD PRIMARY KEY (`entry_id`);

--
-- Indexes for table `subscriptions_subscription`
--
ALTER TABLE `subscriptions_subscription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_subscription_user_id_a353e93d_fk_auth_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`entry_id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auth_group`
--
ALTER TABLE `auth_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_group_permissions`
--
ALTER TABLE `auth_group_permissions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_permission`
--
ALTER TABLE `auth_permission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `auth_user`
--
ALTER TABLE `auth_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_user_groups`
--
ALTER TABLE `auth_user_groups`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_user_user_permissions`
--
ALTER TABLE `auth_user_user_permissions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `book_appointment`
--
ALTER TABLE `book_appointment`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `django_admin_log`
--
ALTER TABLE `django_admin_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `django_content_type`
--
ALTER TABLE `django_content_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `django_migrations`
--
ALTER TABLE `django_migrations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `moodtracker`
--
ALTER TABLE `moodtracker`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `subscriptions_subscription`
--
ALTER TABLE `subscriptions_subscription`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `authtoken_token`
--
ALTER TABLE `authtoken_token`
  ADD CONSTRAINT `authtoken_token_user_id_35299eff_fk_auth_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_user` (`id`);

--
-- Constraints for table `auth_group_permissions`
--
ALTER TABLE `auth_group_permissions`
  ADD CONSTRAINT `auth_group_permissio_permission_id_84c5c92e_fk_auth_perm` FOREIGN KEY (`permission_id`) REFERENCES `auth_permission` (`id`),
  ADD CONSTRAINT `auth_group_permissions_group_id_b120cbf9_fk_auth_group_id` FOREIGN KEY (`group_id`) REFERENCES `auth_group` (`id`);

--
-- Constraints for table `auth_permission`
--
ALTER TABLE `auth_permission`
  ADD CONSTRAINT `auth_permission_content_type_id_2f476e4b_fk_django_co` FOREIGN KEY (`content_type_id`) REFERENCES `django_content_type` (`id`);

--
-- Constraints for table `auth_user_groups`
--
ALTER TABLE `auth_user_groups`
  ADD CONSTRAINT `auth_user_groups_group_id_97559544_fk_auth_group_id` FOREIGN KEY (`group_id`) REFERENCES `auth_group` (`id`),
  ADD CONSTRAINT `auth_user_groups_user_id_6a12ed8b_fk_auth_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_user` (`id`);

--
-- Constraints for table `auth_user_user_permissions`
--
ALTER TABLE `auth_user_user_permissions`
  ADD CONSTRAINT `auth_user_user_permi_permission_id_1fbb5f2c_fk_auth_perm` FOREIGN KEY (`permission_id`) REFERENCES `auth_permission` (`id`),
  ADD CONSTRAINT `auth_user_user_permissions_user_id_a95ead1b_fk_auth_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_user` (`id`);

--
-- Constraints for table `django_admin_log`
--
ALTER TABLE `django_admin_log`
  ADD CONSTRAINT `django_admin_log_content_type_id_c4bce8eb_fk_django_co` FOREIGN KEY (`content_type_id`) REFERENCES `django_content_type` (`id`),
  ADD CONSTRAINT `django_admin_log_user_id_c564eba6_fk_auth_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_user` (`id`);

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subscriptions_subscription`
--
ALTER TABLE `subscriptions_subscription`
  ADD CONSTRAINT `subscriptions_subscription_user_id_a353e93d_fk_auth_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
