-- Database Backup: perumalil_creative
-- Generated on 2026-07-06 08:06:27

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- Table structure for `admin_users`
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

-- Dumping data for `admin_users`

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES ('1', 'admin', '$2y$10$w4Wljj5NuxfwQ5zJ14X9z.BIKLgi1CEeTX1P/CbgN8ypwKp7vzrle', '2026-07-06 11:32:54');

-- --------------------------------------------------------
-- Table structure for `collaborators`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `collaborators`;
CREATE TABLE `collaborators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `collaborators`

INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('1', 'Partner 1', 'assets/images/logo1.png', '1');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('2', 'Partner 2', 'assets/images/logo2.png', '2');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('3', 'Partner 3', 'assets/images/logo3.png', '3');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('4', 'Partner 4', 'assets/images/logo4.png', '4');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('5', 'Partner 5', 'assets/images/logo5.png', '5');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('6', 'Partner 6', 'assets/images/logo6.png', '6');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('7', 'Partner 7', 'assets/images/logo7.png', '7');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('8', 'Partner 8', 'assets/images/logo8.png', '8');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('9', 'Partner 9', 'assets/images/logo9.png', '9');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('10', 'Partner 10', 'assets/images/logo10.png', '10');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('11', 'Partner 11', 'assets/images/logo11.png', '11');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('12', 'Partner 12', 'assets/images/logo12.png', '12');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('13', 'Partner 13', 'assets/images/logo13.png', '13');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('14', 'Partner 14', 'assets/images/logo14.png', '14');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('15', 'Partner 15', 'assets/images/logo15.png', '15');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('16', 'Partner 16', 'assets/images/logo16.png', '16');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('17', 'Partner 17', 'assets/images/logo17.png', '17');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('18', 'Partner 18', 'assets/images/logo18.png', '18');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('19', 'Partner 19', 'assets/images/logo19.png', '19');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('20', 'Partner 20', 'assets/images/logo20.png', '20');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('21', 'Partner 21', 'assets/images/logo21.png', '21');
INSERT INTO `collaborators` (`id`, `name`, `image_path`, `order_index`) VALUES ('22', 'Partner 22', 'assets/images/logo22.png', '22');

-- --------------------------------------------------------
-- Table structure for `faq_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `faq_items`;
CREATE TABLE `faq_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `faq_items`

INSERT INTO `faq_items` (`id`, `question`, `answer`, `order_index`) VALUES ('1', 'How much does a static website cost?', 'The Static Website Development cost varies based on the specific complexity and layout features required by your business. We engineer highly optimized, customized, and lightweight static layout architectures. Contact our team for an exact, tailored pricing blueprint.', '0');
INSERT INTO `faq_items` (`id`, `question`, `answer`, `order_index`) VALUES ('2', 'Why does my business need a website and digital marketing?', 'They establish your online credibility, help you reach more local and global customers, and are vital for staying competitive in today\'s digital landscape. Local businesses that lack proper search positioning lose significant growth opportunities to regional competitors.', '1');
INSERT INTO `faq_items` (`id`, `question`, `answer`, `order_index`) VALUES ('3', 'What video and photography services do you offer?', 'We specialize in high-end cinematic promotional videos, continuous social media Reels creation, corporate photography shoots, product visuals, and local event branding coverages designed to capture attention and communicate your brand concept clearly.', '2');
INSERT INTO `faq_items` (`id`, `question`, `answer`, `order_index`) VALUES ('4', 'How can SEO improve my website\'s visibility?', 'SEO optimizes your digital codebase structure, heading levels, and metadata keywords so that search engines like Google rank you higher in organic search results. This directly translates to increased visitors, targeted local business leads, and solid commercial growth.', '3');
INSERT INTO `faq_items` (`id`, `question`, `answer`, `order_index`) VALUES ('5', 'Do you offer custom packages?', 'Absolutely! We can compile a completely customized, scalable marketing and design bundle matching your unique business objectives, timeline constraints, and creative budget. Reach out via our booking blueprint form to request a custom quote.', '4');

-- --------------------------------------------------------
-- Table structure for `inquiries`
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

-- Dumping data for `inquiries`

-- --------------------------------------------------------
-- Table structure for `intro_pillars`
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

-- Dumping data for `intro_pillars`

INSERT INTO `intro_pillars` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('1', '24/7 Support', 'clock', 'We provide continuous support to help your business grow without interruption.', '0.1', '0');
INSERT INTO `intro_pillars` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('2', 'Creative Design', 'palette', 'We create modern and attractive designs for websites, ads, and branding.', '0.2', '1');
INSERT INTO `intro_pillars` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('3', 'SEO Optimization', 'trending-up', 'Improve your website ranking and reach more customers through search engines.', '0.3', '2');

-- --------------------------------------------------------
-- Table structure for `portfolio_items`
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `portfolio_items`

INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('1', 'video', 'Cinematic Event Shoots', 'Vazhakulam Parish Easter Celebration', 'High-quality color-graded 4K cinematic aftermovie capture.', 'https://www.youtube.com/', 'assets/images/portfolio_festival.png', '0');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('2', 'video', 'Instagram Reels Promo', 'Premium Instagram Reel Package', 'Viral short-form media packages driving customer engagement.', 'https://www.youtube.com/', 'assets/images/portfolio_reel.png', '1');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('3', 'video', 'Corporate Videography', 'High-End Corporate Promos', 'Capturing brand concepts clearly to drive tangible results.', 'https://www.youtube.com/', 'assets/images/portfolio_marketing.png', '2');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('4', 'web', 'UI/UX & Web Solutions', 'Luxury Architecture UI Design', 'Modern Awwwards-inspired layout with WebGL fluid backdrops.', 'https://perumalilcreative.com/', 'assets/images/portfolio_web.png', '0');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('5', 'web', 'Dynamic Web Applications', 'Continuous Social Management System', 'Highly scalable dynamic system with automated scheduler modules.', 'https://perumalilcreative.com/scheduler', 'assets/images/portfolio_social.png', '1');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('6', 'design', 'Brand Identity & Stationery', 'Minimalist Luxury Brand Identity', 'Sophisticated stationery system with hot-foil copper stamping on premium textured paper.', '#', 'assets/images/portfolio_brand.png', '0');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('7', 'design', 'Editorial Layout Design', 'High-End Fashion Magazine Spread', 'Minimalist layout design featuring large-format serif typography and dramatic editorial visuals.', '#', 'assets/images/portfolio_editorial.png', '1');
INSERT INTO `portfolio_items` (`id`, `category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES ('8', 'design', 'Cinematic Poster Design', 'Sophisticated Film Key Art', 'Dramatic minimalist movie poster composition with bold typographic treatment and artistic grading.', '#', 'assets/images/portfolio_poster.png', '2');

-- --------------------------------------------------------
-- Table structure for `pricing_packages`
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

-- Dumping data for `pricing_packages`

INSERT INTO `pricing_packages` (`id`, `name`, `price_label`, `price`, `features`, `delay`, `is_popular`, `order_index`) VALUES ('1', 'Video Production', 'Starting From', '5,999', 'Professional Cinematic Shoot\nAdvanced Editing Included\n4K High Quality Output\nFast 2–3 Day Delivery', '0.1', '1', '0');
INSERT INTO `pricing_packages` (`id`, `name`, `price_label`, `price`, `features`, `delay`, `is_popular`, `order_index`) VALUES ('2', 'Web Development', 'Starting From', '3,999', 'Single Page – &#8377;3999\nMulti Page – &#8377;6999\nDynamic Website – &#8377;6999+\nHosting (3GB) – &#8377;2999\nDomain not included', '0.2', '0', '1');
INSERT INTO `pricing_packages` (`id`, `name`, `price_label`, `price`, `features`, `delay`, `is_popular`, `order_index`) VALUES ('3', 'Graphic Design', 'Starting From', '200', 'Logo Design\nBrochure Design\nPoster Design\nCustom Graphic Work', '0.3', '0', '2');
INSERT INTO `pricing_packages` (`id`, `name`, `price_label`, `price`, `features`, `delay`, `is_popular`, `order_index`) VALUES ('4', 'Meta & Google Ads', 'Starting from ( Monthly )', '2,000', 'Strategic Campaign Planning\nAudience Targeting\nBudget Management\nLead Generation', '0.4', '0', '3');

-- --------------------------------------------------------
-- Table structure for `services`
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

-- Dumping data for `services`

INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('1', 'SEO Services', 'search', 'Boost your website ranking on search engines and get more organic traffic.', '0.1', '0');
INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('2', 'Digital Marketing', 'globe', 'Promote your business through social media, ads, and online campaigns.', '0.2', '1');
INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('3', 'Advertising', 'megaphone', 'Creative advertising solutions to promote your brand effectively.', '0.3', '2');
INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('4', 'Website Design', 'laptop', 'Professional and responsive websites tailored to your business.', '0.4', '3');
INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('5', 'ID Card Design', 'contact', 'Custom ID card designs for companies, schools, and organizations.', '0.5', '4');
INSERT INTO `services` (`id`, `title`, `icon`, `description`, `delay`, `order_index`) VALUES ('6', 'Graphic Design', 'palette', 'Creative designs, branding materials, and graphic solutions.', '0.6', '5');

-- --------------------------------------------------------
-- Table structure for `site_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `site_settings`

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_detail1_desc', 'Whether engineering a custom festival poster, printing event graphics, shooting a single Instagram Reel, or developing high-end layouts, we align every single pixel with surgical accuracy.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_detail1_title', 'Meticulous Detail');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_detail2_desc', 'Aesthetic excellence is only half the battle. We integrate localized SEO architectures, targeted engagement, and strong calls-to-action to ensure your visual assets deliver actual commercial success.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_detail2_title', 'Strategic Growth');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_philosophy_desc', 'We believe that Vazhakulam, Kerala is home to some of the most vibrant local businesses, community organizations, and visual artists. They deserve digital architectures and marketing campaigns that command respect on a global stage.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_philosophy_tagline', 'Our Philosophy');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_philosophy_title', 'Global Standards,<br>Local Heritage');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_us_desc1', 'Perumalil Creative is a professional advertising, branding, and digital marketing company dedicated to helping businesses grow and stand out. We provide complete solutions including SEO, digital marketing, advertising, website design, and graphic design.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_us_desc2', 'We also specialize in creative services like momento design, ID card design, and custom branding materials. Our goal is to build strong brand identity and deliver high-quality creative solutions that drive results.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_us_subtitle', 'Perumalil Creative – Your Partner in Digital Growth & Branding');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_us_tagline', 'About Us');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('about_us_title', 'Your Partner in<br>Digital Growth');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('contact_address', 'First Floor, Kaimalayil Plaza<br>Opposite Viswajyothy College, Avoly P.O.<br>Vazhakulam, Kerala - 686670');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('contact_desc', 'Ready to elevate your digital footprint? Send us your blueprint or select an agency package below.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('contact_email', 'hello@perumalilcreative.com');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('contact_headline', 'Let\'s build<br>something<br>enduring');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('contact_phone1', '+91 99460 20716');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('contact_phone2', '+91 99460 20724');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('footer_copyright', '&copy; 2026 Perumalil Creative. Engineered in Vazhakulam, Kerala. All rights reserved.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('hero_coords', '9.9487° N, 76.6294° E');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('hero_mission_text', 'We design premium creative identities, cinematic brand reels, and luxury digital architectures built on strict mathematical grid parameters.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_col_left_desc', 'We provide SEO, digital marketing, website design, and creative branding solutions to grow your business online.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_col_left_tagline', 'Best Digital Marketing Solutions');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_col_left_title', 'Grow Your Business');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_col_right_desc', 'We help businesses grow with powerful digital marketing strategies, creative designs, and strong online presence.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_col_right_tagline', 'Let\'s grow your business together');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_col_right_title', 'Design & Marketing');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_vision_tagline', 'Our Visual Vision');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_vision_text', 'We specialize in high-quality video production for your brand stories and advertising campaigns. Elevate your business with compelling videos that capture attention, convey concepts clearly, and drive results.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('intro_vision_title', 'Video Wizards<br>Bringing Stories<br>to Life');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('location_maps_link', 'https://maps.app.goo.gl/sVnxVtQPHHKY5nWz8');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('map_latitude', '9.9487');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('map_longitude', '76.6294');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('seo_description', 'Perumalil Creative is a premier digital agency based in Vazhakulam, Kerala. We specialize in digital marketing, high-end graphic design, Instagram Reels production, event branding, and professional web solutions.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('seo_keywords', 'Perumalil Creative, Digital Agency Kerala, Marketing Vazhakulam, Graphic Design Vazhakulam, Instagram Reels Kerala, Web Design Kerala, Event Branding Kerala, Parish Visuals Kerala');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('seo_title', 'Perumalil Creative | Premium Digital Agency in Vazhakulam, Kerala');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('testimonial_author', 'Dijo J Perumaly');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('testimonial_quote', 'Small businesses are the heart of our communities, built with passion and hard work. We believe every owner deserves the chance to shine online, regardless of their budget. We\'re here to craft marketing strategies that tell your story and connect you with your customers, helping your dreams grow.');
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES ('testimonial_role', 'Managing Director, Perumalil Creative');

-- --------------------------------------------------------
-- Table structure for `team_members`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `team_members`;
CREATE TABLE `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `team_members`

INSERT INTO `team_members` (`id`, `name`, `role`, `image_path`, `order_index`) VALUES ('1', 'Dijo J Perumaly', 'Managing Director', 'assets/images/Dijo.gif', '0');
INSERT INTO `team_members` (`id`, `name`, `role`, `image_path`, `order_index`) VALUES ('2', 'Manoj', 'Technical Director', 'assets/images/manoj.gif', '1');
INSERT INTO `team_members` (`id`, `name`, `role`, `image_path`, `order_index`) VALUES ('3', 'Sojin', 'Creative Developer', 'assets/images/sojin.gif', '2');
INSERT INTO `team_members` (`id`, `name`, `role`, `image_path`, `order_index`) VALUES ('4', 'Sam', 'Creative Designer', 'assets/images/sam.jpg', '3');

SET FOREIGN_KEY_CHECKS=1;
