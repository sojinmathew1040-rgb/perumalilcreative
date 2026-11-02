-- ========================================================
-- Perumalil Creative - Database Backup Dump
-- Backup Date & Time: 2026-11-02 21:05:21 (IST)
-- Database: `perumalil_creative`
-- Host: localhost
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `perumalil_creative` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `perumalil_creative`;

-- --------------------------------------------------------
-- Table structure for table `admin_users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `admin_users` (1 rows)
INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES
('1', 'admin', '$2y$10$w4Wljj5NuxfwQ5zJ14X9z.BIKLgi1CEeTX1P/CbgN8ypwKp7vzrle', '2026-07-06 11:32:54');

-- --------------------------------------------------------
-- Table structure for table `collaborators`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `collaborators`;
CREATE TABLE `collaborators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `collaborators` (22 rows)
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES
('1', 'Partner 1', 'assets/images/logo1.png', '1'),
('2', 'Partner 2', 'assets/images/logo2.png', '2'),
('3', 'Partner 3', 'assets/images/logo3.png', '3'),
('4', 'Partner 4', 'assets/images/logo4.png', '4'),
('5', 'Partner 5', 'assets/images/logo5.png', '5'),
('6', 'Partner 6', 'assets/images/logo6.png', '6'),
('7', 'Partner 7', 'assets/images/logo7.png', '7'),
('8', 'Partner 8', 'assets/images/logo8.png', '8'),
('9', 'Partner 9', 'assets/images/logo9.png', '9'),
('10', 'Partner 10', 'assets/images/logo10.png', '10'),
('11', 'Partner 11', 'assets/images/logo11.png', '11'),
('12', 'Partner 12', 'assets/images/logo12.png', '12'),
('13', 'Partner 13', 'assets/images/logo13.png', '13'),
('14', 'Partner 14', 'assets/images/logo14.png', '14'),
('15', 'Partner 15', 'assets/images/logo15.png', '15'),
('16', 'Partner 16', 'assets/images/logo16.png', '16'),
('17', 'Partner 17', 'assets/images/logo17.png', '17'),
('18', 'Partner 18', 'assets/images/logo18.png', '18'),
('19', 'Partner 19', 'assets/images/logo19.png', '19'),
('20', 'Partner 20', 'assets/images/logo20.png', '20'),
('21', 'Partner 21', 'assets/images/logo21.png', '21'),
('22', 'Partner 22', 'assets/images/logo22.png', '22');

-- --------------------------------------------------------
-- Table structure for table `faq_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `faq_items`;
CREATE TABLE `faq_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `faq_items` (5 rows)
INSERT INTO `faq_items` (`id`, `question`, `answer`, `order_index`) VALUES
('1', 'How much does a static website cost?', 'The Static Website Development cost varies based on the specific complexity and layout features required by your business. We engineer highly optimized, customized, and lightweight static layout architectures. Contact our team for an exact, tailored pricing blueprint.', '0'),
('2', 'Why does my business need a website and digital marketing?', 'They establish your online credibility, help you reach more local and global customers, and are vital for staying competitive in today\'s digital landscape. Local businesses that lack proper search positioning lose significant growth opportunities to regional competitors.', '1'),
('3', 'What video and photography services do you offer?', 'We specialize in high-end cinematic promotional videos, continuous social media Reels creation, corporate photography shoots, product visuals, and local event branding coverages designed to capture attention and communicate your brand concept clearly.', '2'),
('4', 'How can SEO improve my website\'s visibility?', 'SEO optimizes your digital codebase structure, heading levels, and metadata keywords so that search engines like Google rank you higher in organic search results. This directly translates to increased visitors, targeted local business leads, and solid commercial growth.', '3'),
('5', 'Do you offer custom packages?', 'Absolutely! We can compile a completely customized, scalable marketing and design bundle matching your unique business objectives, timeline constraints, and creative budget. Reach out via our booking blueprint form to request a custom quote.', '4');

-- --------------------------------------------------------
-- Table structure for table `inquiries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `package_slug` varchar(100) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `intro_pillars`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `intro_pillars`;
CREATE TABLE `intro_pillars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `delay` varchar(10) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `intro_pillars` (3 rows)
INSERT INTO `intro_pillars` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES
('1', '24/7 Support', 'clock', 'We provide continuous support to help your business grow without interruption.', '0.1', '0'),
('2', 'Creative Design', 'palette', 'We create modern and attractive designs for websites, ads, and branding.', '0.2', '1'),
('3', 'SEO Optimization', 'trending-up', 'Improve your website ranking and reach more customers through search engines.', '0.3', '2');

-- --------------------------------------------------------
-- Table structure for table `portfolio_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `portfolio_categories`;
CREATE TABLE `portfolio_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `portfolio_categories` (3 rows)
INSERT INTO `portfolio_categories` (`id`, `slug`, `name`, `order_index`) VALUES
('1', 'video', 'Video Production', '1'),
('2', 'web', 'Web Development', '0'),
('3', 'design', 'Graphic Design', '2');

-- --------------------------------------------------------
-- Table structure for table `portfolio_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `portfolio_items`;
CREATE TABLE `portfolio_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `tag` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `link` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_launching_soon` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `portfolio_items` (19 rows)
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`, `is_launching_soon`) VALUES
('1', 'video', 'Cinematic Event Shoots', 'Vazhakulam Parish Easter Celebration', 'High-quality color-graded 4K cinematic aftermovie capture.', 'https://www.youtube.com/', 'assets/images/portfolio_festival.png', '1', '0'),
('2', 'video', 'Instagram Reels Promo', 'Premium Instagram Reel Package', 'Viral short-form media packages driving customer engagement.', 'https://www.youtube.com/', 'assets/images/portfolio_reel.png', '2', '0'),
('3', 'video', 'Corporate Videography', 'High-End Corporate Promos', 'Capturing brand concepts clearly to drive tangible results.', 'https://www.youtube.com/', 'assets/images/portfolio_marketing.png', '3', '0'),
('6', 'design', 'Brand Identity & Stationery', 'Minimalist Luxury Brand Identity', 'Sophisticated stationery system with hot-foil copper stamping on premium textured paper.', '#', 'assets/images/portfolio_brand.png', '1', '0'),
('7', 'design', 'Editorial Layout Design', 'High-End Fashion Magazine Spread', 'Minimalist layout design featuring large-format serif typography and dramatic editorial visuals.', '#', 'assets/images/portfolio_editorial.png', '2', '0'),
('8', 'design', 'Cinematic Poster Design', 'Sophisticated Film Key Art', 'Dramatic minimalist movie poster composition with bold typographic treatment and artistic grading.', '#', 'assets/images/portfolio_poster.png', '3', '0'),
('9', 'web', 'Luxury Jewellery & E-Commerce Web Architecture', 'K.P. Varkey & Sons', 'Modern gold & diamond boutique e-commerce web platform engineered with interactive catalogue browsing and luxury aesthetic styling.', 'https://kpvarkeyandsons.com/', 'assets/images/portfolio_kpvarkeyandsons.jpg', '1', '0'),
('10', 'web', 'Fashion, Apparel & Comfort Wear E-Commerce', 'Don\'s Magic', 'Modern lifestyle clothing and comfort-wear online store engineered with responsive catalogue browsing, cart workflow, and checkout integration.', 'https://donsmagic.com/', 'assets/images/portfolio_donsmagic.jpg', '2', '0'),
('11', 'web', 'Hyperlocal Multi-Vendor Marketplace', 'Ente Angadi', 'High-performance digital marketplace connecting regional vendors, community commerce, and customers with seamless cart flow.', 'https://enteangadi.com/', 'assets/images/portfolio_enteangadi.jpg', '3', '0'),
('12', 'web', 'Industrial & Commercial LPG Pipeline Solutions', 'National LPG Pipeline Service', 'Clean commercial service portal engineered for domestic and industrial gas pipeline installation, safety audits, and rapid quote requests.', 'https://lpgpipelineservice.com/', 'assets/images/portfolio_lpgpipelineservice.jpg', '4', '0'),
('13', 'web', 'Global Education & Overseas Admissions Portal', 'GracePath Educational Consultancy', 'Sophisticated education and career navigation portal providing student counseling, verified university admissions, and visa guidance workflows.', 'https://gracepath.in/', 'assets/images/portfolio_gracepath.jpg', '5', '0'),
('14', 'web', 'Luxury Hospitality & Resort Estates Showcase', 'The K Collection', 'Ultra-luxury nature resort architecture featuring K-Mansion, K-Manor, and K-Estates with cinematic visual framing and reservation inquiry workflows.', 'https://thekcollection.in/', 'assets/images/portfolio_thekcollection.jpg', '6', '0'),
('15', 'web', 'Contemporary Visual Arts & Furniture Exhibition', 'OXO The Gallery', 'Editorial digital exhibition showcase designed with museum-grade typography and contemporary visual framing for curated artistic collections.', 'https://oxothegallery.com/', 'assets/images/portfolio_oxothegallery.jpg', '7', '0'),
('16', 'web', 'Launching Soon • Theatrical & Culinary Arts', 'Daba Magic', 'Modern digital platform currently in active development. Engineering an enchanting fusion of fine dining and theatrical entertainment.', 'https://dabamagic.com/', 'assets/images/portfolio_launching_soon.jpg', '11', '1'),
('17', 'web', 'Launching Soon • International Healthcare Network', 'Health Jobs Global', 'Global clinical staffing and medical talent portal currently under active development to connect healthcare professionals across worldwide hospital networks.', 'https://healthjobsglobal.com/', 'assets/images/portfolio_launching_soon.jpg', '12', '1'),
('18', 'web', 'Launching Soon • Pan-India Clinical Staffing', 'Health Jobs India', 'Targeted healthcare career exchange platform currently in development, dedicated to nationwide clinical recruitment and verified medical staffing.', 'https://healthjobsglobal.in/', 'assets/images/portfolio_launching_soon.jpg', '13', '1'),
('19', 'web', 'Eco-Agri Tourism & Nature Resort', 'Food Forest Kanthalloor', 'Bespoke nature-stay and eco-resort digital experience currently under active construction, featuring organic apple orchards and highland cottages.', 'https://foodforestkanthalloor.com/', 'assets/images/1792123895_Screenshot 2026-10-16 094056.png', '10', '0'),
('20', 'web', 'Spiritual Centre & Live Streaming Community Portal', 'Tabore Prarthanalayam Pala', 'Serene community portal featuring retreat schedules, live streaming architectures, prayer request dispatching, and ministry updates.', 'https://taborepala.org/', 'assets/images/portfolio_taborepala.jpg', '8', '0'),
('21', 'web', 'US Mortgage Processing & Corporate Financial Portal', 'Principle 1 Professional Services', 'Executive professional services website with corporate telemetry, service breakdowns, and strategic consultation booking pipelines.', 'https://principle1pro.com/', 'assets/images/portfolio_principle1pro.jpg', '9', '0');

-- --------------------------------------------------------
-- Table structure for table `pricing_packages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pricing_packages`;
CREATE TABLE `pricing_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price_label` varchar(255) NOT NULL,
  `price` varchar(50) NOT NULL,
  `features` text NOT NULL,
  `delay` varchar(10) NOT NULL,
  `is_popular` tinyint(1) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `pricing_packages` (4 rows)
INSERT INTO `pricing_packages` (`id`, `name`, `price_label`, `price`, `features`, `delay`, `is_popular`, `order_index`) VALUES
('1', 'Video Production', 'Starting From', '5,999', 'Professional Cinematic Shoot\nAdvanced Editing Included\n4K High Quality Output\nFast 2–3 Day Delivery', '0.1', '1', '0'),
('2', 'Web Development', 'Starting From', '3,999', 'Single Page – &#8377;3999\nMulti Page – &#8377;6999\nDynamic Website – &#8377;6999+\nHosting (3GB) – &#8377;2999\nDomain not included', '0.2', '0', '1'),
('3', 'Graphic Design', 'Starting From', '200', 'Logo Design\nBrochure Design\nPoster Design\nCustom Graphic Work', '0.3', '0', '2'),
('4', 'Meta & Google Ads', 'Starting from ( Monthly )', '2,000', 'Strategic Campaign Planning\nAudience Targeting\nBudget Management\nLead Generation', '0.4', '0', '3');

-- --------------------------------------------------------
-- Table structure for table `services`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `delay` varchar(10) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `services` (6 rows)
INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES
('1', 'SEO Services', 'search', 'Boost your website ranking on search engines and get more organic traffic.', '0.1', '0'),
('2', 'Digital Marketing', 'globe', 'Promote your business through social media, ads, and online campaigns.', '0.2', '1'),
('3', 'Advertising', 'megaphone', 'Creative advertising solutions to promote your brand effectively.', '0.3', '2'),
('4', 'Website Design', 'laptop', 'Professional and responsive websites tailored to your business.', '0.4', '3'),
('5', 'ID Card Design', 'contact', 'Custom ID card designs for companies, schools, and organizations.', '0.5', '4'),
('6', 'Graphic Design', 'palette', 'Creative designs, branding materials, and graphic solutions.', '0.6', '5');

-- --------------------------------------------------------
-- Table structure for table `site_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `site_settings` (75 rows)
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('about_detail1_desc', 'Whether engineering a custom festival poster, printing event graphics, shooting a single Instagram Reel, or developing high-end layouts, we align every single pixel with surgical accuracy.'),
('about_detail1_title', 'Meticulous Detail'),
('about_detail2_desc', 'Aesthetic excellence is only half the battle. We integrate localized SEO architectures, targeted engagement, and strong calls-to-action to ensure your visual assets deliver actual commercial success.'),
('about_detail2_title', 'Strategic Growth'),
('about_philosophy_desc', 'We believe that Vazhakulam, Kerala is home to some of the most vibrant local businesses, community organizations, and visual artists. They deserve digital architectures and marketing campaigns that command respect on a global stage.'),
('about_philosophy_tagline', 'Our Philosophy'),
('about_philosophy_title', 'Global Standards,<br>Local Heritage'),
('about_us_desc1', 'Perumalil Creative is a professional advertising, branding, and digital marketing company dedicated to helping businesses grow and stand out. We provide complete solutions including SEO, digital marketing, advertising, website design, and graphic design.'),
('about_us_desc2', 'We also specialize in creative services like momento design, ID card design, and custom branding materials. Our goal is to build strong brand identity and deliver high-quality creative solutions that drive results.'),
('about_us_subtitle', 'Perumalil Creative – Your Partner in Digital Growth & Branding'),
('about_us_tagline', 'About Us'),
('about_us_title', 'Your Partner in<br>Digital Growth'),
('ai_bot_name', 'Nova'),
('ai_enabled', '0'),
('ai_greeting_desc', 'How can I help you today?'),
('ai_greeting_title', 'Hi! I am Nova 👋'),
('contact_address', 'First Floor, Kaimalayil Plaza<br>Opposite Viswajyothy College, Avoly P.O.<br>Vazhakulam, Kerala - 686670'),
('contact_desc', 'Ready to elevate your digital footprint? Send us your blueprint or select an agency package below.'),
('contact_email', 'hello@perumalilcreative.com'),
('contact_headline', 'Let\'s build<br>something<br>enduring'),
('contact_phone1', '+91 99460 20716'),
('contact_phone2', '+91 99460 20724'),
('footer_copyright', '&copy; 2026 Perumalil Creative. Engineered in Vazhakulam, Kerala. All rights reserved.'),
('gemini_api_key', ''),
('hero_color_glow', '#38bdf8'),
('hero_color_headline', '#ffffff'),
('hero_color_mission', '#cbd5e1'),
('hero_coords', '9.9487° N, 76.6294° E'),
('hero_headline_font', 'Syne'),
('hero_headline_part1', 'Design Your'),
('hero_headline_part2', 'Every Occasion.'),
('hero_marquee_color1', '#10b981'),
('hero_marquee_color2', '#84cc16'),
('hero_marquee_color3', '#ffffff'),
('hero_marquee_color4', '#06b6d4'),
('hero_marquee_font', 'Space Grotesk'),
('hero_marquee_mode', 'words'),
('hero_marquee_opacity', '0.45'),
('hero_marquee_row1_style', 'filled'),
('hero_marquee_row1_text', 'PERUMALIL CREATIVE • '),
('hero_marquee_row2_style', 'outline'),
('hero_marquee_row2_text', 'CREATIVE DIRECTION • CINEMATIC PRODUCTION • BRAND ARCHITECTURE • DIGITAL EXPERIENCES • '),
('hero_marquee_speed', '90'),
('hero_mission_text', 'We design premium creative identities, cinematic brand reels, and luxury digital architectures built on strict mathematical grid parameters.'),
('intro_col_left_desc', 'We provide SEO, digital marketing, website design, and creative branding solutions to grow your business online.'),
('intro_col_left_tagline', 'Best Digital Marketing Solutions'),
('intro_col_left_title', 'Grow Your Business'),
('intro_col_right_desc', 'We help businesses grow with powerful digital marketing strategies, creative designs, and strong online presence.'),
('intro_col_right_tagline', 'Let\'s grow your business together'),
('intro_col_right_title', 'Design & Marketing');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('intro_vision_tagline', 'Our Visual Vision'),
('intro_vision_text', 'We specialize in high-quality video production for your brand stories and advertising campaigns. Elevate your business with compelling videos that capture attention, convey concepts clearly, and drive results.'),
('intro_vision_title', 'Video Wizards<br>Bringing Stories<br>to Life'),
('launching_soon_template_image', 'assets/images/portfolio_launching_soon.jpg'),
('location_maps_link', 'https://maps.app.goo.gl/sVnxVtQPHHKY5nWz8'),
('map_latitude', '9.9487'),
('map_longitude', '76.6294'),
('portfolio_category_order_init', '1'),
('portfolio_orders_normalized_v1', '1'),
('seo_description', 'Perumalil Creative is a premier digital agency based in Vazhakulam, Kerala. We specialize in digital marketing, high-end graphic design, Instagram Reels production, event branding, and professional web solutions.'),
('seo_keywords', 'Perumalil Creative, Digital Agency Kerala, Marketing Vazhakulam, Graphic Design Vazhakulam, Instagram Reels Kerala, Web Design Kerala, Event Branding Kerala, Parish Visuals Kerala'),
('seo_title', 'Perumalil Creative | Premium Digital Agency in Vazhakulam, Kerala'),
('site_color_accent', '#0066ff'),
('site_color_bg', '#000000'),
('site_color_body', '#a0a0ab'),
('site_color_card', '#1b0e2f'),
('site_color_heading', '#ffffff'),
('site_color_muted', '#64748b'),
('site_color_subheading', '#38bdf8'),
('site_font_body', 'Plus Jakarta Sans'),
('site_font_editorial', 'Outfit'),
('site_font_heading', 'Syne'),
('testimonial_author', 'Dijo J Perumaly'),
('testimonial_quote', 'Small businesses are the heart of our communities, built with passion and hard work. We believe every owner deserves the chance to shine online, regardless of their budget. We\'re here to craft marketing strategies that tell your story and connect you with your customers, helping your dreams grow.'),
('testimonial_role', 'Managing Director, Perumalil Creative');

-- --------------------------------------------------------
-- Table structure for table `team_members`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `team_members`;
CREATE TABLE `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `team_members` (4 rows)
INSERT INTO `team_members` (`id`, `name`, `role`, `image_path`, `order_index`) VALUES
('1', 'Dijo J Perumaly', 'Managing Director', 'assets/images/Dijo.gif', '0'),
('2', 'Manoj', 'Technical Director', 'assets/images/manoj.gif', '1'),
('3', 'Sojin', 'Creative Developer', 'assets/images/sojin.gif', '2'),
('4', 'Sam', 'Creative Designer', 'assets/images/sam.jpg', '3');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
