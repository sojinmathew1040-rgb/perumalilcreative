<?php
// setup_db_logic.php - Auto-seeding database schema and initial data

try {
    // 1. Create admin_users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default admin user if empty (password is admin123)
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admin_users`");
    if ($stmt->fetchColumn() == 0) {
        $default_username = 'admin';
        $default_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $insertAdmin = $pdo->prepare("INSERT INTO `admin_users` (`username`, `password_hash`) VALUES (?, ?)");
        $insertAdmin->execute([$default_username, $default_hash]);
    }

    // 2. Create site_settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `site_settings` (
        `setting_key` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed site settings
    $settings = [
        'seo_title' => 'Perumalil Creative | Premium Digital Agency in Vazhakulam, Kerala',
        'seo_description' => 'Perumalil Creative is a premier digital agency based in Vazhakulam, Kerala. We specialize in digital marketing, high-end graphic design, Instagram Reels production, event branding, and professional web solutions.',
        'seo_keywords' => 'Perumalil Creative, Digital Agency Kerala, Marketing Vazhakulam, Graphic Design Vazhakulam, Instagram Reels Kerala, Web Design Kerala, Event Branding Kerala, Parish Visuals Kerala',
        'hero_mission_text' => 'We design premium creative identities, cinematic brand reels, and luxury digital architectures built on strict mathematical grid parameters.',
        'hero_coords' => '9.9487° N, 76.6294° E',
        
        'intro_col_left_tagline' => 'Best Digital Marketing Solutions',
        'intro_col_left_title' => 'Grow Your Business',
        'intro_col_left_desc' => 'We provide SEO, digital marketing, website design, and creative branding solutions to grow your business online.',
        
        'intro_col_right_tagline' => 'Let\'s grow your business together',
        'intro_col_right_title' => 'Design & Marketing',
        'intro_col_right_desc' => 'We help businesses grow with powerful digital marketing strategies, creative designs, and strong online presence.',
        
        'intro_vision_tagline' => 'Our Visual Vision',
        'intro_vision_title' => 'Video Wizards<br>Bringing Stories<br>to Life',
        'intro_vision_text' => 'We specialize in high-quality video production for your brand stories and advertising campaigns. Elevate your business with compelling videos that capture attention, convey concepts clearly, and drive results.',
        
        'about_philosophy_tagline' => 'Our Philosophy',
        'about_philosophy_title' => 'Global Standards,<br>Local Heritage',
        'about_philosophy_desc' => 'We believe that Vazhakulam, Kerala is home to some of the most vibrant local businesses, community organizations, and visual artists. They deserve digital architectures and marketing campaigns that command respect on a global stage.',
        
        'about_detail1_title' => 'Meticulous Detail',
        'about_detail1_desc' => 'Whether engineering a custom festival poster, printing event graphics, shooting a single Instagram Reel, or developing high-end layouts, we align every single pixel with surgical accuracy.',
        
        'about_detail2_title' => 'Strategic Growth',
        'about_detail2_desc' => 'Aesthetic excellence is only half the battle. We integrate localized SEO architectures, targeted engagement, and strong calls-to-action to ensure your visual assets deliver actual commercial success.',
        
        'about_us_tagline' => 'About Us',
        'about_us_title' => 'Your Partner in<br>Digital Growth',
        'about_us_subtitle' => 'Perumalil Creative – Your Partner in Digital Growth & Branding',
        'about_us_desc1' => 'Perumalil Creative is a professional advertising, branding, and digital marketing company dedicated to helping businesses grow and stand out. We provide complete solutions including SEO, digital marketing, advertising, website design, and graphic design.',
        'about_us_desc2' => 'We also specialize in creative services like momento design, ID card design, and custom branding materials. Our goal is to build strong brand identity and deliver high-quality creative solutions that drive results.',
        
        'testimonial_quote' => 'Small businesses are the heart of our communities, built with passion and hard work. We believe every owner deserves the chance to shine online, regardless of their budget. We\'re here to craft marketing strategies that tell your story and connect you with your customers, helping your dreams grow.',
        'testimonial_author' => 'Dijo J Perumaly',
        'testimonial_role' => 'Managing Director, Perumalil Creative',
        
        'contact_headline' => 'Let\'s build<br>something<br>enduring',
        'contact_desc' => 'Ready to elevate your digital footprint? Send us your blueprint or select an agency package below.',
        'contact_phone1' => '+91 99460 20716',
        'contact_phone2' => '+91 99460 20724',
        'contact_email' => 'hello@perumalilcreative.com',
        'contact_address' => 'First Floor, Kaimalayil Plaza<br>Opposite Viswajyothy College, Avoly P.O.<br>Vazhakulam, Kerala - 686670',
        
        'location_maps_link' => 'https://maps.app.goo.gl/sVnxVtQPHHKY5nWz8',
        'map_latitude' => '9.9487',
        'map_longitude' => '76.6294',
        'footer_copyright' => '&copy; 2026 Perumalil Creative. Engineered in Vazhakulam, Kerala. All rights reserved.'
    ];

    $insertSetting = $pdo->prepare("INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    foreach ($settings as $key => $val) {
        $insertSetting->execute([$key, $val]);
    }

    // 3. Create services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `services` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `icon` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `delay` VARCHAR(10) NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `services`");
    if ($stmt->fetchColumn() == 0) {
        $services = [
            ['SEO Services', 'search', 'Boost your website ranking on search engines and get more organic traffic.', '0.1', 0],
            ['Digital Marketing', 'globe', 'Promote your business through social media, ads, and online campaigns.', '0.2', 1],
            ['Advertising', 'megaphone', 'Creative advertising solutions to promote your brand effectively.', '0.3', 2],
            ['Website Design', 'laptop', 'Professional and responsive websites tailored to your business.', '0.4', 3],
            ['ID Card Design', 'contact', 'Custom ID card designs for companies, schools, and organizations.', '0.5', 4],
            ['Graphic Design', 'palette', 'Creative designs, branding materials, and graphic solutions.', '0.6', 5]
        ];
        $insertService = $pdo->prepare("INSERT INTO `services` (`title`, `icon`, `description`, `delay`, `order_index`) VALUES (?, ?, ?, ?, ?)");
        foreach ($services as $srv) {
            $insertService->execute($srv);
        }
    }

    // 4. Create pricing_packages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pricing_packages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `price_label` VARCHAR(255) NOT NULL,
        `price` VARCHAR(50) NOT NULL,
        `features` TEXT NOT NULL,
        `delay` VARCHAR(10) NOT NULL,
        `is_popular` TINYINT(1) DEFAULT 0,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `pricing_packages`");
    if ($stmt->fetchColumn() == 0) {
        $packages = [
            ['Video Production', 'Starting From', '5,999', "Professional Cinematic Shoot\nAdvanced Editing Included\n4K High Quality Output\nFast 2–3 Day Delivery", '0.1', 1, 0],
            ['Web Development', 'Starting From', '3,999', "Single Page – &#8377;3999\nMulti Page – &#8377;6999\nDynamic Website – &#8377;6999+\nHosting (3GB) – &#8377;2999\nDomain not included", '0.2', 0, 1],
            ['Graphic Design', 'Starting From', '200', "Logo Design\nBrochure Design\nPoster Design\nCustom Graphic Work", '0.3', 0, 2],
            ['Meta & Google Ads', 'Starting from ( Monthly )', '2,000', "Strategic Campaign Planning\nAudience Targeting\nBudget Management\nLead Generation", '0.4', 0, 3]
        ];
        $insertPack = $pdo->prepare("INSERT INTO `pricing_packages` (`name`, `price_label`, `price`, `features`, `delay`, `is_popular`, `order_index`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($packages as $pkg) {
            $insertPack->execute($pkg);
        }
    }

    // 4.5. Create portfolio_categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `portfolio_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmtCatCount = $pdo->query("SELECT COUNT(*) FROM `portfolio_categories`");
    if ($stmtCatCount->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `portfolio_categories` (`slug`, `name`, `order_index`) VALUES 
            ('web', 'Web Development', 0),
            ('video', 'Video Production', 1),
            ('design', 'Graphic Design', 2)
        ");
    }

    // 5. Create portfolio_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `portfolio_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` VARCHAR(50) NOT NULL,
        `tag` VARCHAR(255) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `link` VARCHAR(255) NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `portfolio_items`");
    if ($stmt->fetchColumn() == 0) {
        $portfolio = [
            // Video Production (category: video)
            ['video', 'Cinematic Event Shoots', 'Vazhakulam Parish Easter Celebration', 'High-quality color-graded 4K cinematic aftermovie capture.', 'https://www.youtube.com/', 'assets/images/portfolio_festival.png', 0],
            ['video', 'Instagram Reels Promo', 'Premium Instagram Reel Package', 'Viral short-form media packages driving customer engagement.', 'https://www.youtube.com/', 'assets/images/portfolio_reel.png', 1],
            ['video', 'Corporate Videography', 'High-End Corporate Promos', 'Capturing brand concepts clearly to drive tangible results.', 'https://www.youtube.com/', 'assets/images/portfolio_marketing.png', 2],
            
            // Web Development (category: web)
            ['web', 'Luxury Jewellery & E-Commerce Web Architecture', 'K.P. Varkey & Sons', 'Modern gold & diamond boutique e-commerce web platform engineered with interactive catalogue browsing and luxury aesthetic styling.', 'https://kpvarkeyandsons.com/', 'assets/images/portfolio_kpvarkeyandsons.jpg', 1],
            ['web', 'Fashion, Apparel & Comfort Wear E-Commerce', "Don's Magic", 'Modern lifestyle clothing and comfort-wear online store engineered with responsive catalogue browsing, cart workflow, and checkout integration.', 'https://donsmagic.com/', 'assets/images/portfolio_donsmagic.jpg', 2],
            ['web', 'Hyperlocal Multi-Vendor Marketplace', 'Ente Angadi', 'High-performance digital marketplace connecting regional vendors, community commerce, and customers with seamless cart flow.', 'https://enteangadi.com/', 'assets/images/portfolio_enteangadi.jpg', 3],
            ['web', 'Industrial & Commercial LPG Pipeline Solutions', 'National LPG Pipeline Service', 'Clean commercial service portal engineered for domestic and industrial gas pipeline installation, safety audits, and rapid quote requests.', 'https://lpgpipelineservice.com/', 'assets/images/portfolio_lpgpipelineservice.jpg', 4],
            ['web', 'Global Education & Overseas Admissions Portal', 'GracePath Educational Consultancy', 'Sophisticated education and career navigation portal providing student counseling, verified university admissions, and visa guidance workflows.', 'https://gracepath.in/', 'assets/images/portfolio_gracepath.jpg', 5],
            ['web', 'Luxury Hospitality & Resort Estates Showcase', 'The K Collection', 'Ultra-luxury nature resort architecture featuring K-Mansion, K-Manor, and K-Estates with cinematic visual framing and reservation inquiry workflows.', 'https://thekcollection.in/', 'assets/images/portfolio_thekcollection.jpg', 6],
            ['web', 'Contemporary Visual Arts & Furniture Exhibition', 'OXO The Gallery', 'Editorial digital exhibition showcase designed with museum-grade typography and contemporary visual framing for curated artistic collections.', 'https://oxothegallery.com/', 'assets/images/portfolio_oxothegallery.jpg', 7],
            ['web', 'Spiritual Centre & Live Streaming Community Portal', 'Tabore Prarthanalayam Pala', 'Serene community portal featuring retreat schedules, live streaming architectures, prayer request dispatching, and ministry updates.', 'https://taborepala.org/', 'assets/images/portfolio_taborepala.jpg', 8],
            ['web', 'US Mortgage Processing & Corporate Financial Portal', 'Principle 1 Professional Services', 'Executive professional services website with corporate telemetry, service breakdowns, and strategic consultation booking pipelines.', 'https://principle1pro.com/', 'assets/images/portfolio_principle1pro.jpg', 9],
            ['web', 'Launching Soon • Theatrical & Culinary Arts', 'Daba Magic', 'Modern digital platform currently in active development. Engineering an enchanting fusion of fine dining and theatrical entertainment.', 'https://dabamagic.com/', 'assets/images/portfolio_launching_soon.jpg', 10],
            ['web', 'Launching Soon • International Healthcare Network', 'Health Jobs Global', 'Global clinical staffing and medical talent portal currently under active development to connect healthcare professionals across worldwide hospital networks.', 'https://healthjobsglobal.com/', 'assets/images/portfolio_launching_soon.jpg', 11],
            ['web', 'Launching Soon • Pan-India Clinical Staffing', 'Health Jobs India', 'Targeted healthcare career exchange platform currently in development, dedicated to nationwide clinical recruitment and verified medical staffing.', 'https://healthjobsglobal.in/', 'assets/images/portfolio_launching_soon.jpg', 12],
            ['web', 'Launching Soon • Eco-Agri Tourism & Nature Resort', 'Food Forest Kanthalloor', 'Bespoke nature-stay and eco-resort digital experience currently under active construction, featuring organic apple orchards and highland cottages.', 'https://foodforestkanthalloor.com/', 'assets/images/portfolio_launching_soon.jpg', 13],
            
            // Graphic Design (category: design)
            ['design', 'Brand Identity & Stationery', 'Minimalist Luxury Brand Identity', 'Sophisticated stationery system with hot-foil copper stamping on premium textured paper.', '#', 'assets/images/portfolio_brand.png', 0],
            ['design', 'Editorial Layout Design', 'High-End Fashion Magazine Spread', 'Minimalist layout design featuring large-format serif typography and dramatic editorial visuals.', '#', 'assets/images/portfolio_editorial.png', 1],
            ['design', 'Cinematic Poster Design', 'Sophisticated Film Key Art', 'Dramatic minimalist movie poster composition with bold typographic treatment and artistic grading.', '#', 'assets/images/portfolio_poster.png', 2]
        ];
        $insertPort = $pdo->prepare("INSERT INTO `portfolio_items` (`category`, `tag`, `title`, `description`, `link`, `image_path`, `order_index`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($portfolio as $port) {
            $insertPort->execute($port);
        }
    }

    // 6. Create team_members table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `team_members` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `role` VARCHAR(255) NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `team_members`");
    if ($stmt->fetchColumn() == 0) {
        $team = [
            ['Dijo J Perumaly', 'Managing Director', 'assets/images/Dijo.gif', 0],
            ['Manoj', 'Technical Director', 'assets/images/manoj.gif', 1],
            ['Sojin', 'Creative Developer', 'assets/images/sojin.gif', 2],
            ['Sam', 'Creative Designer', 'assets/images/sam.jpg', 3]
        ];
        $insertTeam = $pdo->prepare("INSERT INTO `team_members` (`name`, `role`, `image_path`, `order_index`) VALUES (?, ?, ?, ?)");
        foreach ($team as $member) {
            $insertTeam->execute($member);
        }
    }

    // 7. Create faq_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `faq_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `question` TEXT NOT NULL,
        `answer` TEXT NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `faq_items`");
    if ($stmt->fetchColumn() == 0) {
        $faqs = [
            ['How much does a static website cost?', 'The Static Website Development cost varies based on the specific complexity and layout features required by your business. We engineer highly optimized, customized, and lightweight static layout architectures. Contact our team for an exact, tailored pricing blueprint.', 0],
            ['Why does my business need a website and digital marketing?', 'They establish your online credibility, help you reach more local and global customers, and are vital for staying competitive in today\'s digital landscape. Local businesses that lack proper search positioning lose significant growth opportunities to regional competitors.', 1],
            ['What video and photography services do you offer?', 'We specialize in high-end cinematic promotional videos, continuous social media Reels creation, corporate photography shoots, product visuals, and local event branding coverages designed to capture attention and communicate your brand concept clearly.', 2],
            ['How can SEO improve my website\'s visibility?', 'SEO optimizes your digital codebase structure, heading levels, and metadata keywords so that search engines like Google rank you higher in organic search results. This directly translates to increased visitors, targeted local business leads, and solid commercial growth.', 3],
            ['Do you offer custom packages?', 'Absolutely! We can compile a completely customized, scalable marketing and design bundle matching your unique business objectives, timeline constraints, and creative budget. Reach out via our booking blueprint form to request a custom quote.', 4]
        ];
        $insertFaq = $pdo->prepare("INSERT INTO `faq_items` (`question`, `answer`, `order_index`) VALUES (?, ?, ?)");
        foreach ($faqs as $faq) {
            $insertFaq->execute($faq);
        }
    }

    // 8. Create intro_pillars table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `intro_pillars` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `icon` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `delay` VARCHAR(10) NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `intro_pillars`");
    if ($stmt->fetchColumn() == 0) {
        $pillars = [
            ['24/7 Support', 'clock', 'We provide continuous support to help your business grow without interruption.', '0.1', 0],
            ['Creative Design', 'palette', 'We create modern and attractive designs for websites, ads, and branding.', '0.2', 1],
            ['SEO Optimization', 'trending-up', 'Improve your website ranking and reach more customers through search engines.', '0.3', 2]
        ];
        $insertPillar = $pdo->prepare("INSERT INTO `intro_pillars` (`title`, `icon`, `description`, `delay`, `order_index`) VALUES (?, ?, ?, ?, ?)");
        foreach ($pillars as $pil) {
            $insertPillar->execute($pil);
        }
    }

    // 9. Create collaborators table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `collaborators` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `order_index` INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SELECT COUNT(*) FROM `collaborators`");
    if ($stmt->fetchColumn() == 0) {
        $insertCollab = $pdo->prepare("INSERT INTO `collaborators` (`name`, `image_path`, `order_index`) VALUES (?, ?, ?)");
        for ($i = 1; $i <= 22; $i++) {
            $insertCollab->execute(["Partner $i", "assets/images/logo$i.png", $i]);
        }
    }

    // 10. Create inquiries table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `inquiries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `package_slug` VARCHAR(100) NOT NULL,
        `package_name` VARCHAR(255) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `message` TEXT NOT NULL,
        `status` VARCHAR(50) DEFAULT 'New',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
