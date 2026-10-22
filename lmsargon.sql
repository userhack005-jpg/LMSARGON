-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 11:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lmsargon`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name` varchar(140) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'general',
  `version` varchar(30) NOT NULL DEFAULT '1.0.0',
  `author` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `admin_route` varchar(150) DEFAULT NULL,
  `frontend_route` varchar(150) DEFAULT NULL,
  `config_json` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `installed_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`id`, `slug`, `name`, `category`, `version`, `author`, `description`, `admin_route`, `frontend_route`, `config_json`, `is_active`, `installed_at`, `updated_at`) VALUES
(7, 'page_builder', 'Visual Page Builder & Dynamic CMS', 'frontend_cms', '1.0.0', 'Core Architecture Team', 'Database-driven Drag & Drop Visual Page Builder with Rich Text Editor, Direct Image Upload, Interactive Quiz Blocks, CTA Buttons, Custom URL Slugs (e.g. /about-us), and Dynamic Homepage routing.', 'addons/page-builder', 'about-us', '{\n    \"enable_root_homepage\": \"1\",\n    \"show_top_navbar\": \"1\",\n    \"container_width\": \"1180px\"\n}', 1, '2026-10-16 06:31:36', '2026-10-16 06:41:25'),
(82, 'announcements', 'Dynamic Announcements Ticker', 'cms_widget', '2.2.0', 'Core Architecture Team', '100% self-contained top notification bar with external CSS/JS, zero-overlap RTL/LTR infinite marquee, merged inline links, and granular RBAC.', 'addons/announcements', '', '{\n    \"badge_text\": \"NEW\",\n    \"filter_mode\": \"category\",\n    \"category\": \"all\",\n    \"announcement_id\": \"\",\n    \"scroll_mode\": \"rtl\",\n    \"scroll_speed\": \"22\",\n    \"bg_color\": \"#10b981\",\n    \"text_color\": \"#04130d\",\n    \"badge_bg\": \"#04130d\",\n    \"badge_color\": \"#10b981\",\n    \"dismissible\": \"1\"\n}', 1, '2026-10-22 05:21:48', '2026-10-22 05:21:48'),
(83, 'reviews', 'Student Reviews & Testimonial Slider', 'cms_widget', '2.3.0', 'Core Architecture Team', 'Self-contained student reviews with Auto-Scroll (Smooth RTL/LTR Marquee & Fade-In/Fade-Out), Dual Dark/Light Theme Color Pickers, and Granular RBAC.', 'addons/reviews', '', '{\n    \"badge\": \"WALL OF LOVE\",\n    \"section_title\": \"What Parents & Students Say\",\n    \"subtitle\": \"Real verified stories from our global learning community.\",\n    \"category\": \"all\",\n    \"columns\": \"3\",\n    \"auto_scroll\": \"1\",\n    \"anim_style\": \"smooth_scroll\",\n    \"scroll_dir\": \"rtl\",\n    \"scroll_speed\": \"28\",\n    \"color_mode\": \"auto\",\n    \"card_bg_dark\": \"#171a26\",\n    \"text_dark\": \"#f8fafc\",\n    \"star_dark\": \"#f59e0b\",\n    \"card_bg_light\": \"#ffffff\",\n    \"text_light\": \"#0f172a\",\n    \"star_light\": \"#d97706\"\n}', 1, '2026-10-22 05:22:46', '2026-10-22 05:22:46'),
(84, 'faqs', 'Dynamic FAQ Accordion', 'cms_widget', '2.1.0', 'Core Architecture Team', 'Self-contained FAQ accordion add-on with external CSS/JS assets, category filtering, alignment controls, and Google FAQPage SEO Schema.', 'addons/faqs', '', '{\n    \"default_category\": \"General\",\n    \"expand_first\": \"1\",\n    \"box_position\": \"center\",\n    \"text_align\": \"left\",\n    \"border_color\": \"#232738\",\n    \"accent_color\": \"#10b981\"\n}', 1, '2026-10-22 05:22:56', '2026-10-22 05:22:56'),
(86, 'newsletter', 'Dynamic Newsletter & Lead Capture', 'cms_widget', '4.1.0', 'Core Architecture Team', '100% self-contained dynamic multi-form template & lead capture engine with automatic Page Builder schema, live template dropdown, CSV export, and granular RBAC.', 'addons/newsletter', '', '{\n    \"template_id\": \"default\",\n    \"badge\": \"FREE STUDY KIT & UPDATES\",\n    \"heading\": \"Master Abacus & Mental Math Faster\",\n    \"subheading\": \"Get free weekly practice worksheets, speed-math tricks, and scholarship alerts straight to your inbox.\",\n    \"btn_text\": \"\",\n    \"privacy_note\": \"\\ud83d\\udd12 100% Privacy Guaranteed. Unsubscribe anytime.\",\n    \"is_floating\": \"0\",\n    \"color_mode\": \"auto\",\n    \"bg_color\": \"#0d1f1a\",\n    \"border_color\": \"#10b981\",\n    \"text_color\": \"#f3f4f6\",\n    \"btn_bg\": \"#10b981\",\n    \"btn_text_col\": \"#04130d\",\n    \"card_bg_light\": \"#ecfdf5\",\n    \"text_light\": \"#064e3b\"\n}', 1, '2026-10-22 05:44:28', '2026-10-22 05:44:28');

-- --------------------------------------------------------

--
-- Table structure for table `cms_announcements`
--

CREATE TABLE `cms_announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'General',
  `message` varchar(500) NOT NULL,
  `link_text` varchar(100) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_announcements`
--

INSERT INTO `cms_announcements` (`id`, `category`, `message`, `link_text`, `link_url`, `is_active`, `created_at`) VALUES
(1, 'Admissions', '🚀 Admissions Open for 2026 International Abacus Batch — Get 30% Early Bird Scholarship!', 'Claim Scholarship →', '/login', 1, '2026-10-18 00:13:04'),
(2, 'Admissions', '🏆 Global Mental Math Olympiad Registration closes this Sunday!', 'Register Now →', '/about-us', 1, '2026-10-18 00:13:04'),
(3, 'Updates', '⚡ New Interactive Practice Portal 2.0 is now live for all enrolled students.', '', '', 1, '2026-10-18 00:13:04'),
(5, 'Admissions', 'dsdsdsd', 'dsdsdsdsd111', 'sds', 1, '2026-10-20 03:05:35');

-- --------------------------------------------------------

--
-- Table structure for table `cms_components`
--

CREATE TABLE `cms_components` (
  `id` int(10) UNSIGNED NOT NULL,
  `component_key` varchar(60) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_icon` varchar(30) NOT NULL DEFAULT '⊞',
  `category` varchar(50) NOT NULL DEFAULT 'content',
  `addon_slug` varchar(60) NOT NULL DEFAULT 'page_builder',
  `default_schema_json` longtext DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_components`
--

INSERT INTO `cms_components` (`id`, `component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`, `created_at`) VALUES
(1, 'hero_banner', 'Hero Banner Section', '★', 'layout', 'page_builder', '{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}', 1, 1, '2026-10-16 06:31:36'),
(2, 'rich_text', 'Advanced Rich Text Editor', '¶', 'content', 'page_builder', '{\"html\":\"<h2>About Our Vision<\\/h2><p>Write unrestricted formatted content here using the visual WYSIWYG toolbar, custom headings, lists, highlights, and code blocks.<\\/p>\"}', 1, 2, '2026-10-16 06:31:36'),
(3, 'image_media', 'Image & Media Showcase', '⊞', 'media', 'page_builder', '{\"image_url\":\"\",\"caption\":\"Featured Visual Showcase\",\"alt_text\":\"Platform Image\",\"width\":\"100%\"}', 1, 3, '2026-10-16 06:31:36'),
(4, 'cta_buttons', 'Action Button Group', '◈', 'interactive', 'page_builder', '{\"title\":\"Ready to get started with us?\",\"primary_text\":\"Get Started Free\",\"primary_url\":\"\\/login\",\"secondary_text\":\"Contact Support\",\"secondary_url\":\"#contact\"}', 1, 4, '2026-10-16 06:31:36'),
(5, 'interactive_quiz', 'Interactive Quiz / Assessment', '?', 'interactive', 'page_builder', '{\"question\":\"Which mental math tool uses beads sliding on rods for rapid calculation?\",\"option_a\":\"Slide Rule\",\"option_b\":\"Abacus (Soroban)\",\"option_c\":\"Pascaline\",\"option_d\":\"Astrolabe\",\"correct_index\":\"B\",\"explanation\":\"Correct! The Abacus (Soroban) is used globally for rapid mental arithmetic.\"}', 1, 5, '2026-10-16 06:31:36'),
(6, 'feature_grid', '3-Column Feature Cards', '❖', 'layout', 'page_builder', '{\"section_title\":\"Why Choose Our Platform\",\"card1_title\":\"Modular Architecture\",\"card1_desc\":\"Plug-and-play zip add-ons with one-time auto-database initialization.\",\"card2_title\":\"Dynamic SEO Slugs\",\"card2_desc\":\"Define custom clean URLs like \\/about-us or \\/pricing effortlessly.\",\"card3_title\":\"Interactive Widgets\",\"card3_desc\":\"Embed quizzes, rich media, and action buttons from the DB registry.\"}', 1, 6, '2026-10-16 06:31:36'),
(7, 'custom_html', 'Raw HTML / Embed Block', '<>', 'advanced', 'page_builder', '{\"raw_code\":\"<div class=\\\"custom-embed-box\\\">Custom HTML, YouTube iframe, or Widget Script<\\/div>\"}', 1, 7, '2026-10-16 06:31:36'),
(8, 'announcement_bar', 'Announcement Top Bar (Ticker)', '📢', 'header', 'announcements', '{\"badge_text\":\"NEW\",\"filter_mode\":\"category\",\"category\":\"all\",\"announcement_id\":\"\",\"scroll_mode\":\"rtl\",\"scroll_speed\":\"22\",\"bg_color\":\"#10b981\",\"text_color\":\"#04130d\",\"badge_bg\":\"#04130d\",\"badge_color\":\"#10b981\",\"dismissible\":\"1\"}', 1, 1, '2026-10-17 19:52:53'),
(9, 'split_hero', 'Dynamic Split Hero (With Floating Badges)', '⚡', 'layout', 'page_builder', '{\"badge\":\"NEXT-GEN LEARNING ECOSYSTEM\",\"heading\":\"Master Mental Math & Analytical Skills Faster\",\"highlight_word\":\"Faster\",\"subheading\":\"Interactive live classes, automated assessments, and global competitions designed for modern learners.\",\"primary_text\":\"Start Free Trial\",\"primary_url\":\"\\/login\",\"secondary_text\":\"Explore Curriculum\",\"secondary_url\":\"#courses\",\"image_url\":\"\",\"stat1_val\":\"15,000+\",\"stat1_label\":\"Active Learners\",\"stat2_val\":\"4.9 \\u2605\",\"stat2_label\":\"Global Rating\",\"trust_text\":\"Trusted by parents across UAE, UK, USA & India\",\"accent_color\":\"#10b981\"}', 1, 2, '2026-10-17 19:52:53'),
(10, 'video_hero', 'Video Popup Hero Section', '▶', 'media', 'page_builder', '{\"badge\":\"WATCH 2-MIN DEMO\",\"title\":\"See How Our Interactive Classroom Works\",\"subtitle\":\"Experience real-time abacus simulations, instant quiz feedback, and personalized mentoring.\",\"video_url\":\"https:\\/\\/www.youtube.com\\/embed\\/dQw4w9WgXcQ\",\"poster_url\":\"\",\"btn_text\":\"Play Interactive Tour\",\"pulse_color\":\"#10b981\",\"overlay_color\":\"#11131c\"}', 1, 3, '2026-10-17 19:52:53'),
(11, 'card_grid', 'Multi-Column Card Grid (Courses / Services)', '⊞', 'content', 'page_builder', '{\"badge\":\"POPULAR PROGRAMS\",\"section_title\":\"Explore Our Structured Programs\",\"subtitle\":\"Flexible multi-column grid for courses, services, or features with custom colors.\",\"columns\":\"3\",\"card_bg\":\"#171a26\",\"border_color\":\"#232738\",\"accent_color\":\"#10b981\",\"c1_tag\":\"LEVEL 01\",\"c1_title\":\"Foundation Abacus\",\"c1_desc\":\"Build speed and photographic memory for ages 5\\u20138.\",\"c1_price\":\"$49\\/mo\",\"c1_btn\":\"Enroll Now\",\"c1_url\":\"\\/login\",\"c1_img\":\"\",\"c2_tag\":\"LEVEL 02\",\"c2_title\":\"Intermediate Soroban\",\"c2_desc\":\"Multi-digit mental multiplication and rapid division.\",\"c2_price\":\"$69\\/mo\",\"c2_btn\":\"Enroll Now\",\"c2_url\":\"\\/login\",\"c2_img\":\"\",\"c3_tag\":\"PRO TRACK\",\"c3_title\":\"Olympiad Mastery\",\"c3_desc\":\"Competitive speed training for international tournaments.\",\"c3_price\":\"$89\\/mo\",\"c3_btn\":\"Enroll Now\",\"c3_url\":\"\\/login\",\"c3_img\":\"\",\"c4_tag\":\"\",\"c4_title\":\"\",\"c4_desc\":\"\",\"c4_price\":\"\",\"c4_btn\":\"\",\"c4_url\":\"\",\"c4_img\":\"\"}', 1, 4, '2026-10-17 19:52:53'),
(412, 'quick_math_quiz', 'Quick Math Quiz', '?', 'content', 'quick_math_quiz', '{\"prompt\":\"What is\",\"first_value\":\"2\",\"operator\":\"+\",\"second_value\":\"2\",\"submit_label\":\"Submit Answer\",\"correct_message\":\"Correct! Well done.\",\"incorrect_message\":\"Not quite. Please try again.\",\"accent_color\":\"#2563eb\"}', 1, 0, '2026-10-22 03:28:59'),
(413, 'quick_quiz_box', 'Interactive Quick Quiz (Q&A)', '?', 'header', 'quick_quiz', '{\"badge_text\":\"QUIZ\",\"question_text\":\"What is 2 + 2?\",\"correct_answer\":\"4\",\"placeholder_text\":\"Type your answer...\",\"button_text\":\"Check Answer\",\"success_msg\":\"Correct! Well done.\",\"error_msg\":\"Incorrect! Please try again.\",\"bg_color\":\"#0f172a\",\"text_color\":\"#f8fafc\",\"accent_color\":\"#10b981\"}', 1, 10, '2026-10-22 03:47:58'),
(541, 'testimonial_slider', 'Student Reviews & Star Slider', '★', 'social_proof', 'reviews', '{\"badge\":\"WALL OF LOVE\",\"section_title\":\"What Parents & Students Say\",\"subtitle\":\"Real verified stories from our global learning community.\",\"category\":\"all\",\"columns\":\"3\",\"auto_scroll\":\"1\",\"anim_style\":\"smooth_scroll\",\"scroll_dir\":\"rtl\",\"scroll_speed\":\"28\",\"color_mode\":\"auto\",\"card_bg_dark\":\"#171a26\",\"text_dark\":\"#f8fafc\",\"star_dark\":\"#f59e0b\",\"card_bg_light\":\"#ffffff\",\"text_light\":\"#0f172a\",\"star_light\":\"#d97706\"}', 1, 9, '2026-10-22 03:59:09'),
(551, 'faq_accordion', 'Dynamic FAQ Accordion', '?', 'interactive', 'faqs', '{\"badge\":\"SUPPORT & CLARITY\",\"section_title\":\"Frequently Asked Questions\",\"subtitle\":\"Fetched live from the database. Click any question to expand.\",\"category\":\"all\",\"box_position\":\"center\",\"text_align\":\"left\",\"expand_first\":\"1\",\"border_color\":\"#232738\",\"accent_color\":\"#10b981\"}', 1, 10, '2026-10-22 03:59:17'),
(670, 'newsletter_cta', 'Newsletter / Lead Magnet (Multi-Form)', '✉', 'header', 'newsletter', '{\"template_id\":\"default\",\"badge\":\"FREE STUDY KIT & UPDATES\",\"heading\":\"Master Abacus & Mental Math Faster\",\"subheading\":\"Get free weekly practice worksheets, speed-math tricks, and scholarship alerts straight to your inbox.\",\"btn_text\":\"\",\"privacy_note\":\"🔒 100% Privacy Guaranteed. Unsubscribe anytime.\",\"is_floating\":\"0\",\"color_mode\":\"auto\",\"bg_color\":\"#0d1f1a\",\"border_color\":\"#10b981\",\"text_color\":\"#f3f4f6\",\"btn_bg\":\"#10b981\",\"btn_text_col\":\"#04130d\",\"card_bg_light\":\"#ecfdf5\",\"text_light\":\"#064e3b\"}', 1, 40, '2026-10-22 05:44:30');

-- --------------------------------------------------------

--
-- Table structure for table `cms_faqs`
--

CREATE TABLE `cms_faqs` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'General',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_faqs`
--

INSERT INTO `cms_faqs` (`id`, `category`, `question`, `answer`, `sort_order`, `is_published`, `created_at`) VALUES
(3, 'Certification', 'Do students receive verified certificates upon completion?', 'Every completed level automatically generates a verifiable digital certificate inside your student dashboard.', 3, 1, '2026-10-17 19:52:53'),
(5, 'Generalssass', 'ddemo', 'dfdf', 1, 1, '2026-10-18 20:16:32'),
(6, 'Deno', 'dsds', 'dsds', 1, 1, '2026-10-18 20:19:20'),
(7, 'NEw', 'ddemo', 'dfdffdfdfdfd', 1, 1, '2026-10-18 21:08:53');

-- --------------------------------------------------------

--
-- Table structure for table `cms_footers`
--

CREATE TABLE `cms_footers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `style_key` varchar(50) NOT NULL,
  `columns_count` tinyint(1) NOT NULL DEFAULT 3,
  `sections_json` longtext DEFAULT NULL,
  `copyright_text` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_footers`
--

INSERT INTO `cms_footers` (`id`, `name`, `style_key`, `columns_count`, `sections_json`, `copyright_text`, `is_active`, `updated_at`) VALUES
(6, 'Footer // STYLE_3COL', 'style_3col', 3, '[[{\"id\":\"fblk_1792458129411\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}},{\"id\":\"fblk_1792458138923\",\"type\":\"rich_text\",\"data\":{\"html\":\"<h2>About Our Vision</h2><p>Write unrestricted formatted content here using the visual WYSIWYG toolbar, custom headings, lists, highlights, and code blocks.</p>\"}},{\"id\":\"fblk_1792458150939\",\"type\":\"split_hero\",\"data\":{\"badge\":\"NEXT-GEN LEARNING ECOSYSTEM\",\"heading\":\"Master Mental Math & Analytical Skills Faster\",\"highlight_word\":\"Faster\",\"subheading\":\"Interactive live classes, automated assessments, and global competitions designed for modern learners.\",\"primary_text\":\"Start Free Trial\",\"primary_url\":\"/login\",\"secondary_text\":\"Explore Curriculum\",\"secondary_url\":\"#courses\",\"image_url\":\"\",\"stat1_val\":\"15,000+\",\"stat1_label\":\"Active Learners\",\"stat2_val\":\"4.9 ★\",\"stat2_label\":\"Global Rating\",\"trust_text\":\"Trusted by parents across UAE, UK, USA & India\",\"accent_color\":\"#10b981\"}}],[{\"id\":\"fblk_1792457238838\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}},{\"id\":\"fblk_1792457239061\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}},{\"id\":\"fblk_1792457239269\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}}],[{\"id\":\"fblk_1792458186458\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}}],[]]', '© 2026 All Rights Reserved.', 1, '2026-10-20 18:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `cms_leads`
--

CREATE TABLE `cms_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(160) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_leads`
--

INSERT INTO `cms_leads` (`id`, `email`, `created_at`) VALUES
(1, 'admin@abacuslms.com', '2026-10-17 15:07:55'),
(2, 'admin@gmail.com', '2026-10-20 01:15:48');

-- --------------------------------------------------------

--
-- Table structure for table `cms_newsletter_forms`
--

CREATE TABLE `cms_newsletter_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_name` varchar(120) NOT NULL,
  `show_name` tinyint(1) NOT NULL DEFAULT 0,
  `req_name` tinyint(1) NOT NULL DEFAULT 0,
  `name_placeholder` varchar(150) NOT NULL DEFAULT 'Enter your full name...',
  `show_phone` tinyint(1) NOT NULL DEFAULT 0,
  `req_phone` tinyint(1) NOT NULL DEFAULT 0,
  `phone_placeholder` varchar(150) NOT NULL DEFAULT 'WhatsApp / Mobile number...',
  `show_email` tinyint(1) NOT NULL DEFAULT 1,
  `req_email` tinyint(1) NOT NULL DEFAULT 1,
  `email_placeholder` varchar(150) NOT NULL DEFAULT 'Enter your email address...',
  `btn_text` varchar(90) NOT NULL DEFAULT 'Subscribe →',
  `success_msg` varchar(255) NOT NULL DEFAULT 'Thank you! Your details have been submitted.',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_newsletter_forms`
--

INSERT INTO `cms_newsletter_forms` (`id`, `form_name`, `show_name`, `req_name`, `name_placeholder`, `show_phone`, `req_phone`, `phone_placeholder`, `show_email`, `req_email`, `email_placeholder`, `btn_text`, `success_msg`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Email Newsletter Only', 0, 0, 'Your full name...', 0, 0, 'Mobile number...', 1, 1, 'Enter your email address...', 'Subscribe →', 'Thank you for subscribing to our newsletter!', 0, 1, '2026-10-21 11:13:28', '2026-10-21 11:13:28'),
(2, 'WhatsApp / Mobile Only', 0, 0, 'Your full name...', 1, 1, 'Enter your WhatsApp number...', 0, 0, 'Enter your email...', 'Join on WhatsApp →', 'Great! Your WhatsApp number has been registered.', 0, 1, '2026-10-21 11:13:28', '2026-10-21 11:13:28'),
(3, 'Name + Mobile Callback', 1, 1, 'Enter your full name...', 1, 1, 'Enter 10-digit mobile number...', 0, 0, 'Enter your email...', 'Request Callback →', 'Thank you! Our team will call you shortly.', 0, 1, '2026-10-21 11:13:28', '2026-10-21 22:58:55'),
(4, 'Full Lead Form (Name + WhatsApp + Email)', 1, 1, 'Enter your full name...', 1, 1, 'WhatsApp / Mobile number...', 1, 1, 'Enter your email address...', 'Get Free Study Kit →', 'Success! Your free study kit access is ready.', 1, 1, '2026-10-21 11:13:28', '2026-10-21 11:13:28');

-- --------------------------------------------------------

--
-- Table structure for table `cms_newsletter_leads`
--

CREATE TABLE `cms_newsletter_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `form_name` varchar(120) NOT NULL DEFAULT 'Email Newsletter Only',
  `full_name` varchar(120) NOT NULL DEFAULT '',
  `email` varchar(190) NOT NULL,
  `phone` varchar(45) NOT NULL DEFAULT '',
  `interest_topic` varchar(140) NOT NULL DEFAULT '',
  `lead_tag` varchar(90) NOT NULL DEFAULT 'Website Lead Magnet',
  `source_page` varchar(160) NOT NULL DEFAULT '',
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'subscribed',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_newsletter_leads`
--

INSERT INTO `cms_newsletter_leads` (`id`, `form_id`, `form_name`, `full_name`, `email`, `phone`, `interest_topic`, `lead_tag`, `source_page`, `ip_address`, `status`, `created_at`, `updated_at`) VALUES
(8, 1, 'Email Newsletter Only', '', 'admin@abacuslms.com', '', '', 'Website Lead Magnet', '/index.php/demo', '::1', 'subscribed', '2026-10-21 23:04:17', '2026-10-21 23:04:17'),
(9, 2, 'WhatsApp / Mobile Only', '', '', '9098558792', '', 'Website Lead Magnet', '/index.php/qqa', '::1', 'subscribed', '2026-10-22 00:16:41', '2026-10-22 00:16:41');

-- --------------------------------------------------------

--
-- Table structure for table `cms_pages`
--

CREATE TABLE `cms_pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `blocks_json` longtext DEFAULT NULL,
  `meta_title` varchar(180) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `is_homepage` tinyint(1) NOT NULL DEFAULT 0,
  `show_in_nav` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_pages`
--

INSERT INTO `cms_pages` (`id`, `title`, `slug`, `blocks_json`, `meta_title`, `meta_description`, `status`, `is_homepage`, `show_in_nav`, `created_at`, `updated_at`) VALUES
(21, 'Home', 'home', '[{\"id\":\"blk_1792534874890\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}},{\"id\":\"blk_1792534876722\",\"type\":\"announcement_bar\",\"data\":{\"badge_text\":\"NEW\",\"filter_mode\":\"category\",\"category\":\"all\",\"announcement_id\":\"\",\"scroll_mode\":\"rtl\",\"scroll_speed\":\"22\",\"bg_color\":\"#10b981\",\"text_color\":\"#04130d\",\"badge_bg\":\"#04130d\",\"badge_color\":\"#10b981\",\"dismissible\":\"1\"}},{\"id\":\"blk_1792534877530\",\"type\":\"rich_text\",\"data\":{\"html\":\"<h2>About Our Vision</h2><p>Write unrestricted formatted content here using the visual WYSIWYG toolbar, custom headings, lists, highlights, and code blocks.</p>\"}},{\"id\":\"blk_1792534878706\",\"type\":\"split_hero\",\"data\":{\"badge\":\"NEXT-GEN LEARNING ECOSYSTEM\",\"heading\":\"Master Mental Math & Analytical Skills Faster\",\"highlight_word\":\"Faster\",\"subheading\":\"Interactive live classes, automated assessments, and global competitions designed for modern learners.\",\"primary_text\":\"Start Free Trial\",\"primary_url\":\"/login\",\"secondary_text\":\"Explore Curriculum\",\"secondary_url\":\"#courses\",\"image_url\":\"\",\"stat1_val\":\"15,000+\",\"stat1_label\":\"Active Learners\",\"stat2_val\":\"4.9 ★\",\"stat2_label\":\"Global Rating\",\"trust_text\":\"Trusted by parents across UAE, UK, USA & India\",\"accent_color\":\"#10b981\"}},{\"id\":\"blk_1792534879194\",\"type\":\"image_media\",\"data\":{\"image_url\":\"http://localhost:8080/uploads/cms/cms_1792534933_1792534933_ff86d563a8a0e80c0f68.png\",\"caption\":\"Featured Visual Showcase\",\"alt_text\":\"Platform Image\",\"width\":\"100%\"}},{\"id\":\"blk_1792534880290\",\"type\":\"video_hero\",\"data\":{\"badge\":\"WATCH 2-MIN DEMO\",\"title\":\"See How Our Interactive Classroom Works\",\"subtitle\":\"Experience real-time abacus simulations, instant quiz feedback, and personalized mentoring.\",\"video_url\":\"https://www.youtube.com/embed/dQw4w9WgXcQ\",\"poster_url\":\"\",\"btn_text\":\"Play Interactive Tour\",\"pulse_color\":\"#10b981\",\"overlay_color\":\"#11131c\"}},{\"id\":\"blk_1792534881074\",\"type\":\"cta_buttons\",\"data\":{\"title\":\"Ready to get started with us?\",\"primary_text\":\"Get Started Free\",\"primary_url\":\"/login\",\"secondary_text\":\"Contact Support\",\"secondary_url\":\"#contact\"}},{\"id\":\"blk_1792534881786\",\"type\":\"card_grid\",\"data\":{\"badge\":\"POPULAR PROGRAMS\",\"section_title\":\"Explore Our Structured Programs\",\"subtitle\":\"Flexible multi-column grid for courses, services, or features with custom colors.\",\"columns\":\"2\",\"card_bg\":\"#171a26\",\"border_color\":\"#10b981\",\"accent_color\":\"#10b981\",\"c1_tag\":\"LEVEL 01\",\"c1_title\":\"Foundation Abacus\",\"c1_desc\":\"Build speed and photographic memory for ages 5–8.\",\"c1_price\":\"$49/mo\",\"c1_btn\":\"Enroll Now\",\"c1_url\":\"/login\",\"c1_img\":\"\",\"c2_tag\":\"LEVEL 02\",\"c2_title\":\"Intermediate Soroban\",\"c2_desc\":\"Multi-digit mental multiplication and rapid division.\",\"c2_price\":\"$69/mo\",\"c2_btn\":\"Enroll Now\",\"c2_url\":\"/login\",\"c2_img\":\"\",\"c3_tag\":\"PRO TRACK\",\"c3_title\":\"Olympiad Mastery\",\"c3_desc\":\"Competitive speed training for international tournaments.\",\"c3_price\":\"$89/mo\",\"c3_btn\":\"Enroll Now\",\"c3_url\":\"/login\",\"c3_img\":\"\",\"c4_tag\":\"\",\"c4_title\":\"\",\"c4_desc\":\"\",\"c4_price\":\"\",\"c4_btn\":\"\",\"c4_url\":\"\",\"c4_img\":\"\"}},{\"id\":\"blk_1792534882954\",\"type\":\"interactive_quiz\",\"data\":{\"question\":\"Which mental math tool uses beads sliding on rods for rapid calculation?\",\"option_a\":\"Slide Rule\",\"option_b\":\"Abacus (Soroban)\",\"option_c\":\"Pascaline\",\"option_d\":\"Astrolabe\",\"correct_index\":\"B\",\"explanation\":\"Correct! The Abacus (Soroban) is used globally for rapid mental arithmetic.\"}},{\"id\":\"blk_1792534883858\",\"type\":\"testimonial_slider\",\"data\":{\"badge\":\"WALL OF LOVE\",\"section_title\":\"What Parents & Students Say\",\"subtitle\":\"Real verified stories from our global learning community.\",\"category\":\"all\",\"columns\":\"3\",\"auto_scroll\":\"1\",\"anim_style\":\"smooth_scroll\",\"scroll_dir\":\"rtl\",\"scroll_speed\":\"28\",\"color_mode\":\"auto\",\"card_bg_dark\":\"#171a26\",\"text_dark\":\"#f8fafc\",\"star_dark\":\"#f59e0b\",\"card_bg_light\":\"#ffffff\",\"text_light\":\"#0f172a\",\"star_light\":\"#d97706\"}},{\"id\":\"blk_1792534885970\",\"type\":\"feature_grid\",\"data\":{\"section_title\":\"Why Choose Our Platform\",\"card1_title\":\"Modular Architecture\",\"card1_desc\":\"Plug-and-play zip add-ons with one-time auto-database initialization.\",\"card2_title\":\"Dynamic SEO Slugs\",\"card2_desc\":\"Define custom clean URLs like /about-us or /pricing effortlessly.\",\"card3_title\":\"Interactive Widgets\",\"card3_desc\":\"Embed quizzes, rich media, and action buttons from the DB registry.\"}},{\"id\":\"blk_1792534887906\",\"type\":\"faq_accordion\",\"data\":{\"badge\":\"SUPPORT & CLARITY\",\"section_title\":\"Frequently Asked Questions\",\"subtitle\":\"Fetched live from the database. Click any question to expand.\",\"category\":\"all\",\"box_position\":\"center\",\"text_align\":\"left\",\"expand_first\":\"1\",\"border_color\":\"#232738\",\"accent_color\":\"#10b981\"}},{\"id\":\"blk_1792534888770\",\"type\":\"custom_html\",\"data\":{\"raw_code\":\"<div class=\\\"custom-embed-box\\\">Custom HTML, YouTube iframe, or Widget Script</div>\"}},{\"id\":\"blk_1792535161855\",\"type\":\"newsletter_cta\",\"data\":{\"badge\":\"FREE MENTAL MATH WORKBOOK\",\"heading\":\"Get Weekly Practice Sheets & Exam Alerts\",\"subheading\":\"Join 10,000+ parents and students receiving our curated learning resources.\",\"placeholder\":\"Enter your email address...\",\"btn_text\":\"Subscribe Now\",\"is_floating\":\"1\",\"bg_color\":\"#0d1f1a\",\"border_color\":\"#10b981\",\"text_color\":\"#f3f4f6\",\"btn_bg\":\"#10b981\",\"btn_text_col\":\"#04130d\"}}]', '', '', 'published', 1, 1, '2026-10-20 03:00:55', '2026-10-21 04:00:00'),
(22, 'demo', 'demo', '[{\"id\":\"blk_1792628589271\",\"type\":\"hero_banner\",\"data\":{\"badge\":\"WELCOME TO OUR PLATFORM\",\"heading\":\"Build High-Impact Digital Experiences\",\"subheading\":\"Fully customizable dynamic page section powered by the modular CI4 engine.\",\"btn_text\":\"Explore Now\",\"btn_url\":\"#features\",\"btn_style\":\"primary\",\"align\":\"center\"}},{\"id\":\"blk_1792628591279\",\"type\":\"announcement_bar\",\"data\":{\"badge_text\":\"NEW\",\"filter_mode\":\"category\",\"category\":\"all\",\"announcement_id\":\"\",\"scroll_mode\":\"rtl\",\"scroll_speed\":\"22\",\"bg_color\":\"#10b981\",\"text_color\":\"#04130d\",\"badge_bg\":\"#04130d\",\"badge_color\":\"#10b981\",\"dismissible\":\"1\"}},{\"id\":\"blk_1792628592327\",\"type\":\"rich_text\",\"data\":{\"html\":\"<h2>About Our Vision</h2><p>Write unrestricted formatted content here using the visual WYSIWYG toolbar, custom headings, lists, highlights, and code blocks.</p>\"}},{\"id\":\"blk_1792628593606\",\"type\":\"image_media\",\"data\":{\"image_url\":\"\",\"caption\":\"Featured Visual Showcase\",\"alt_text\":\"Platform Image\",\"width\":\"100%\"}},{\"id\":\"blk_1792628594350\",\"type\":\"video_hero\",\"data\":{\"badge\":\"WATCH 2-MIN DEMO\",\"title\":\"See How Our Interactive Classroom Works\",\"subtitle\":\"Experience real-time abacus simulations, instant quiz feedback, and personalized mentoring.\",\"video_url\":\"https://www.youtube.com/embed/dQw4w9WgXcQ\",\"poster_url\":\"\",\"btn_text\":\"Play Interactive Tour\",\"pulse_color\":\"#10b981\",\"overlay_color\":\"#11131c\"}},{\"id\":\"blk_1792628595303\",\"type\":\"cta_buttons\",\"data\":{\"title\":\"Ready to get started with us?\",\"primary_text\":\"Get Started Free\",\"primary_url\":\"/login\",\"secondary_text\":\"Contact Support\",\"secondary_url\":\"#contact\"}},{\"id\":\"blk_1792628596039\",\"type\":\"card_grid\",\"data\":{\"badge\":\"POPULAR PROGRAMS\",\"section_title\":\"Explore Our Structured Programs\",\"subtitle\":\"Flexible multi-column grid for courses, services, or features with custom colors.\",\"columns\":\"3\",\"card_bg\":\"#171a26\",\"border_color\":\"#232738\",\"accent_color\":\"#10b981\",\"c1_tag\":\"LEVEL 01\",\"c1_title\":\"Foundation Abacus\",\"c1_desc\":\"Build speed and photographic memory for ages 5–8.\",\"c1_price\":\"$49/mo\",\"c1_btn\":\"Enroll Now\",\"c1_url\":\"/login\",\"c1_img\":\"\",\"c2_tag\":\"LEVEL 02\",\"c2_title\":\"Intermediate Soroban\",\"c2_desc\":\"Multi-digit mental multiplication and rapid division.\",\"c2_price\":\"$69/mo\",\"c2_btn\":\"Enroll Now\",\"c2_url\":\"/login\",\"c2_img\":\"\",\"c3_tag\":\"PRO TRACK\",\"c3_title\":\"Olympiad Mastery\",\"c3_desc\":\"Competitive speed training for international tournaments.\",\"c3_price\":\"$89/mo\",\"c3_btn\":\"Enroll Now\",\"c3_url\":\"/login\",\"c3_img\":\"\",\"c4_tag\":\"\",\"c4_title\":\"\",\"c4_desc\":\"\",\"c4_price\":\"\",\"c4_btn\":\"\",\"c4_url\":\"\",\"c4_img\":\"\"}},{\"id\":\"blk_1792628597358\",\"type\":\"interactive_quiz\",\"data\":{\"question\":\"Which mental math tool uses beads sliding on rods for rapid calculation?\",\"option_a\":\"Slide Rule\",\"option_b\":\"Abacus (Soroban)\",\"option_c\":\"Pascaline\",\"option_d\":\"Astrolabe\",\"correct_index\":\"B\",\"explanation\":\"Correct! The Abacus (Soroban) is used globally for rapid mental arithmetic.\"}},{\"id\":\"blk_1792628597950\",\"type\":\"feature_grid\",\"data\":{\"section_title\":\"Why Choose Our Platform\",\"card1_title\":\"Modular Architecture\",\"card1_desc\":\"Plug-and-play zip add-ons with one-time auto-database initialization.\",\"card2_title\":\"Dynamic SEO Slugs\",\"card2_desc\":\"Define custom clean URLs like /about-us or /pricing effortlessly.\",\"card3_title\":\"Interactive Widgets\",\"card3_desc\":\"Embed quizzes, rich media, and action buttons from the DB registry.\"}},{\"id\":\"blk_1792628598622\",\"type\":\"custom_html\",\"data\":{\"raw_code\":\"<div class=\\\"custom-embed-box\\\">Custom HTML, YouTube iframe, or Widget Script</div>\"}},{\"id\":\"blk_1792628599726\",\"type\":\"testimonial_slider\",\"data\":{\"badge\":\"WALL OF LOVE\",\"section_title\":\"What Parents & Students Say\",\"subtitle\":\"Real verified stories from our global learning community.\",\"category\":\"all\",\"columns\":\"3\",\"auto_scroll\":\"1\",\"anim_style\":\"smooth_scroll\",\"scroll_dir\":\"rtl\",\"scroll_speed\":\"28\",\"color_mode\":\"auto\",\"card_bg_dark\":\"#171a26\",\"text_dark\":\"#f8fafc\",\"star_dark\":\"#f59e0b\",\"card_bg_light\":\"#ffffff\",\"text_light\":\"#0f172a\",\"star_light\":\"#d97706\"}},{\"id\":\"blk_1792628601230\",\"type\":\"faq_accordion\",\"data\":{\"badge\":\"SUPPORT & CLARITY\",\"section_title\":\"Frequently Asked Questions\",\"subtitle\":\"Fetched live from the database. Click any question to expand.\",\"category\":\"all\",\"box_position\":\"center\",\"text_align\":\"left\",\"expand_first\":\"1\",\"border_color\":\"#232738\",\"accent_color\":\"#10b981\"}},{\"id\":\"blk_1792628601846\",\"type\":\"newsletter_cta\",\"data\":{\"template_id\":\"default\",\"badge\":\"FREE STUDY KIT & UPDATES\",\"heading\":\"Master Abacus & Mental Math Faster\",\"subheading\":\"Get free weekly practice worksheets, speed-math tricks, and scholarship alerts straight to your inbox.\",\"btn_text\":\"\",\"privacy_note\":\"🔒 100% Privacy Guaranteed. Unsubscribe anytime.\",\"is_floating\":\"1\",\"color_mode\":\"auto\",\"bg_color\":\"#0d1f1a\",\"border_color\":\"#10b981\",\"text_color\":\"#f3f4f6\",\"btn_bg\":\"#10b981\",\"btn_text_col\":\"#04130d\",\"card_bg_light\":\"#ecfdf5\",\"text_light\":\"#064e3b\"}}]', '', '', 'published', 0, 1, '2026-10-21 14:25:00', '2026-10-22 05:54:06'),
(23, 'q', 'q', '[{\"id\":\"blk_1792620773985\",\"type\":\"quick_math_quiz\",\"data\":{\"prompt\":\"What is\",\"first_value\":\"2\",\"operator\":\"+\",\"second_value\":\"2\",\"submit_label\":\"Submit Answer\",\"correct_message\":\"Correct! Well done.\",\"incorrect_message\":\"Not quite. Please try again.\",\"accent_color\":\"#2563eb\"}}]', '', '', 'published', 0, 1, '2026-10-22 03:29:26', '2026-10-22 03:42:57'),
(24, 'qqa', 'qqa', '[{\"id\":\"blk_1792628100260\",\"type\":\"newsletter_cta\",\"data\":{\"template_id\":\"2\",\"badge\":\"FREE STUDY KIT & UPDATES\",\"heading\":\"Master Abacus & Mental Math Faster\",\"subheading\":\"Get free weekly practice worksheets, speed-math tricks, and scholarship alerts straight to your inbox.\",\"btn_text\":\"DEv\",\"privacy_note\":\"🔒 100% Privacy Guaranteed. Unsubscribe anytime.\",\"is_floating\":\"0\",\"color_mode\":\"auto\",\"bg_color\":\"#0d1f1a\",\"border_color\":\"#10b981\",\"text_color\":\"#f3f4f6\",\"btn_bg\":\"#10b981\",\"btn_text_col\":\"#04130d\",\"card_bg_light\":\"#ecfdf5\",\"text_light\":\"#064e3b\"}}]', '', '', 'published', 0, 1, '2026-10-22 05:45:13', '2026-10-22 05:46:04');

-- --------------------------------------------------------

--
-- Table structure for table `cms_quick_quiz_logs`
--

CREATE TABLE `cms_quick_quiz_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_text` varchar(300) NOT NULL,
  `user_answer` varchar(200) NOT NULL,
  `expected_answer` varchar(200) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_quick_quiz_logs`
--

INSERT INTO `cms_quick_quiz_logs` (`id`, `question_text`, `user_answer`, `expected_answer`, `is_correct`, `ip_address`, `created_at`) VALUES
(7, 'What is 2 + 2?', '8', '4', 0, '::1', '2026-10-22 04:03:27'),
(8, 'What is 2 + 2?', '4', '4', 1, '::1', '2026-10-22 04:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `cms_reviews`
--

CREATE TABLE `cms_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `reviewer_name` varchar(120) NOT NULL,
  `designation` varchar(140) NOT NULL DEFAULT 'Verified Student',
  `avatar_url` text DEFAULT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `review_text` text NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_reviews`
--

INSERT INTO `cms_reviews` (`id`, `reviewer_name`, `designation`, `avatar_url`, `rating`, `review_text`, `is_approved`, `created_at`) VALUES
(1, 'Aarav Verma', 'Abacus Grandmaster Batch', NULL, 5, 'The interactive visual mental math sessions completely transformed my calculation speed within 8 weeks!', 1, '2026-10-17 19:52:53'),
(2, 'Sarah Al-Mansoor', 'Parent — Dubai, UAE', NULL, 5, 'Ultra-fast portal, live progress tracking, and brilliant instructors. My kids look forward to every class.', 1, '2026-10-17 19:52:53'),
(3, 'David Kensington', 'London, UK', NULL, 5, 'Clean interface, zero lag on mobile devices, and well-structured learning modules.', 1, '2026-10-17 19:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `cms_testimonials`
--

CREATE TABLE `cms_testimonials` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'General',
  `person_name` varchar(120) NOT NULL,
  `person_role` varchar(120) NOT NULL DEFAULT 'Verified Student',
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `quote` text NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_testimonials`
--

INSERT INTO `cms_testimonials` (`id`, `category`, `person_name`, `person_role`, `rating`, `quote`, `avatar_url`, `sort_order`, `is_approved`, `created_at`) VALUES
(1, 'Parents', 'Sarah Jenkins 1', 'Parent of Grade 5 Student (UK)', 5, 'My son improved his mental math speed in just 6 weeks! The live portal and instant feedback are world-class.', '', 1, 1, '2026-10-19 00:05:38'),
(2, 'Students', 'Aarav Mehta', 'Level 4 Champion (UAE)', 5, 'The interactive practice challenges make learning super fun. I love earning my digital badges!', NULL, 2, 1, '2026-10-19 00:05:38'),
(3, 'Parents', 'David Okafor', 'Parent of Twins (Nigeria)', 5, 'Highly structured curriculum and very supportive teachers. Both my kids look forward to every session.', NULL, 3, 1, '2026-10-19 00:05:38');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `txn_reference` varchar(80) NOT NULL,
  `provider` varchar(40) NOT NULL,
  `payer_name` varchar(120) NOT NULL,
  `payer_email` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `status` enum('success','pending','failed') DEFAULT 'success',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_slug` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_slug`, `role_name`, `description`, `created_at`) VALUES
(1, 'super_admin', 'Super Administrator', 'Full unrestricted system & architecture access', '2026-10-15 17:56:25'),
(2, 'admin', 'Administrator', 'Operations, user management, and portal configuration', '2026-10-15 17:56:25'),
(3, 'teacher', 'Teacher / Instructor', 'Course, batch, and student evaluation management', '2026-10-15 17:56:25'),
(4, 'student', 'Student / Member', 'Learning portal and interactive classroom access', '2026-10-15 17:56:25');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_group`, `setting_value`, `updated_at`) VALUES
(1, 'site_title', 'general', 'Dev', '2026-10-17 02:07:32'),
(2, 'site_logo', 'general', NULL, '2026-10-17 02:07:20'),
(3, 'site_favicon', 'general', 'uploads/branding/favicon_1792183029.png', '2026-10-17 02:07:09'),
(5, 'site_tagline', 'general', 'Enterprise Modular Portal', '2026-10-16 00:07:48'),
(8, 'contact_email', 'general', '', '2026-10-16 00:07:48'),
(9, 'meta_title', 'seo', 'LMS', '2026-10-16 06:18:29'),
(10, 'meta_description', 'seo', 'DevMetaDescription', '2026-10-16 05:41:53'),
(11, 'meta_keywords', 'seo', 'DevMetaDescription,', '2026-10-16 05:41:53'),
(12, 'canonical_url', 'seo', '', '2026-10-16 00:07:48'),
(13, 'robots_txt', 'seo', 'index, follow', '2026-10-16 00:07:48'),
(14, 'og_image', 'seo', NULL, '2026-10-16 00:07:48'),
(15, 'google_analytics_id', 'analytics', 'DevGoogle', '2026-10-16 05:41:53'),
(16, 'google_tag_manager_id', 'analytics', '', '2026-10-16 00:07:48'),
(17, 'meta_pixel_id', 'analytics', '', '2026-10-16 00:07:48'),
(18, 'google_site_verification', 'analytics', '', '2026-10-16 00:07:48'),
(19, 'bing_site_verification', 'analytics', '', '2026-10-16 00:07:48'),
(20, 'meta_domain_verification', 'analytics', '', '2026-10-16 00:07:48'),
(21, 'custom_css', 'scripts', '', '2026-10-16 00:07:48'),
(22, 'header_scripts', 'scripts', '', '2026-10-16 00:07:48'),
(23, 'body_scripts', 'scripts', '', '2026-10-16 00:07:48'),
(24, 'footer_scripts', 'scripts', '', '2026-10-16 00:07:48'),
(340, 'active_theme_mode', 'appearance', 'dark', '2026-10-17 04:22:03'),
(341, 'site_layout_width', 'appearance', 'boxed', '2026-10-20 06:53:47'),
(342, 'default_block_surface', 'appearance', 'card', '2026-10-17 04:22:38'),
(343, 'font_family_sans', 'appearance', 'Poppins', '2026-10-17 04:22:38'),
(344, 'font_family_mono', 'appearance', 'JetBrains Mono', '2026-10-17 04:20:13'),
(345, 'primary_accent_color', 'appearance', '#0c79ed', '2026-10-17 04:23:28'),
(346, 'secondary_accent_color', 'appearance', '#06b6d4', '2026-10-17 04:20:13'),
(347, 'dark_bg_canvas', 'appearance', '#090a0f', '2026-10-17 04:20:13'),
(348, 'dark_bg_surface', 'appearance', '#11131c', '2026-10-17 04:20:13'),
(349, 'dark_text_primary', 'appearance', '#f3f4f6', '2026-10-17 04:20:13'),
(350, 'dark_border_color', 'appearance', '#232738', '2026-10-17 04:20:14'),
(351, 'light_bg_canvas', 'appearance', '#f8fafc', '2026-10-17 04:20:14'),
(352, 'light_bg_surface', 'appearance', '#ffffff', '2026-10-17 04:20:14'),
(353, 'light_text_primary', 'appearance', '#0f172a', '2026-10-17 04:20:14'),
(354, 'light_border_color', 'appearance', '#e2e8f0', '2026-10-17 04:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `password_hash`, `status`, `last_login`, `created_at`) VALUES
(1, 1, 'Super Admin', 'admin@gmail.com', '$2y$10$iG/WgD8h9oY6H1KASUipveegl2K8t3DBfXBUxNCxJvOiC5Iu6cq2C', 'active', '2026-10-21 21:30:51', '2026-10-15 17:56:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cms_announcements`
--
ALTER TABLE `cms_announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_components`
--
ALTER TABLE `cms_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `component_key` (`component_key`);

--
-- Indexes for table `cms_faqs`
--
ALTER TABLE `cms_faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_footers`
--
ALTER TABLE `cms_footers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_leads`
--
ALTER TABLE `cms_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cms_newsletter_forms`
--
ALTER TABLE `cms_newsletter_forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_newsletter_leads`
--
ALTER TABLE `cms_newsletter_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_tag` (`status`,`lead_tag`);

--
-- Indexes for table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cms_quick_quiz_logs`
--
ALTER TABLE `cms_quick_quiz_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_reviews`
--
ALTER TABLE `cms_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_testimonials`
--
ALTER TABLE `cms_testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `txn_reference` (`txn_reference`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_slug` (`role_slug`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `cms_announcements`
--
ALTER TABLE `cms_announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cms_components`
--
ALTER TABLE `cms_components`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=821;

--
-- AUTO_INCREMENT for table `cms_faqs`
--
ALTER TABLE `cms_faqs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cms_footers`
--
ALTER TABLE `cms_footers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cms_leads`
--
ALTER TABLE `cms_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cms_newsletter_forms`
--
ALTER TABLE `cms_newsletter_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cms_newsletter_leads`
--
ALTER TABLE `cms_newsletter_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cms_pages`
--
ALTER TABLE `cms_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `cms_quick_quiz_logs`
--
ALTER TABLE `cms_quick_quiz_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cms_reviews`
--
ALTER TABLE `cms_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cms_testimonials`
--
ALTER TABLE `cms_testimonials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1204;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
