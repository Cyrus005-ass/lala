-- ============================================
-- FINOVA BANK - Base de données
-- ============================================

CREATE DATABASE IF NOT EXISTS finova_bank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finova_bank;

-- Table: paramètres du site
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) UNIQUE NOT NULL,
  `value` TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: contenu des pages (multilingue)
CREATE TABLE IF NOT EXISTS page_content (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(100) NOT NULL,
  lang VARCHAR(10) NOT NULL DEFAULT 'fr',
  section VARCHAR(100) NOT NULL,
  content TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_content (page_key, lang, section)
);

-- Table: programmes de financement (dons/subventions non remboursables)
CREATE TABLE IF NOT EXISTS programs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) UNIQUE NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  max_amount DECIMAL(15,2),
  min_amount DECIMAL(15,2) DEFAULT 1000.00,
  currency VARCHAR(10) DEFAULT 'USD',
  deadline DATE,
  target_sector VARCHAR(255),
  eligibility_countries TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: traductions des programmes
CREATE TABLE IF NOT EXISTS program_translations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  lang VARCHAR(10) NOT NULL,
  title VARCHAR(255),
  description TEXT,
  objectives TEXT,
  eligibility_criteria TEXT,
  FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
  UNIQUE KEY unique_translation (program_id, lang)
);

-- Table: candidatures
CREATE TABLE IF NOT EXISTS applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(30) UNIQUE NOT NULL,
  program_id INT NOT NULL,
  -- Infos organisation
  org_name VARCHAR(255) NOT NULL,
  org_type ENUM('ngo','association','cooperative','sme','startup','public','other') NOT NULL,
  org_country VARCHAR(100) NOT NULL,
  org_address TEXT,
  org_registration_number VARCHAR(100),
  org_founded_year YEAR,
  org_website VARCHAR(255),
  -- Infos contact
  contact_name VARCHAR(255) NOT NULL,
  contact_title VARCHAR(100),
  contact_email VARCHAR(255) NOT NULL,
  contact_phone VARCHAR(50),
  -- Projet
  project_title VARCHAR(500) NOT NULL,
  project_description TEXT NOT NULL,
  project_objectives TEXT,
  project_beneficiaries TEXT,
  project_duration VARCHAR(100),
  project_location TEXT,
  project_budget DECIMAL(15,2),
  requested_amount DECIMAL(15,2) NOT NULL,
  project_start_date DATE,
  -- Fichiers
  doc_registration VARCHAR(500),
  doc_statutes VARCHAR(500),
  doc_budget_plan VARCHAR(500),
  doc_project_plan VARCHAR(500),
  doc_last_report VARCHAR(500),
  -- Suivi
  status ENUM('pending','under_review','approved','rejected','waitlisted','disbursed') DEFAULT 'pending',
  admin_notes TEXT,
  internal_score TINYINT,
  reviewed_by VARCHAR(100),
  reviewed_at TIMESTAMP NULL,
  disbursement_date DATE NULL,
  disbursement_amount DECIMAL(15,2),
  lang VARCHAR(10) DEFAULT 'fr',
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Table: administrateurs
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(255),
  role ENUM('superadmin','admin','reviewer') DEFAULT 'reviewer',
  is_active TINYINT(1) DEFAULT 1,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: messages de contact
CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(500),
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  replied_at TIMESTAMP NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: FAQ (multilingue)
CREATE TABLE IF NOT EXISTS faq (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lang VARCHAR(10) NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1
);

-- ============================================
-- DONNÉES INITIALES
-- ============================================

-- Admin par défaut (mot de passe: Admin@2024!)
INSERT INTO admins (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@finova.org', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiM3Oq8r5Nn2mJz1xBwHBFxXE7Gy', 'Super Administrateur', 'superadmin');

-- Paramètres
INSERT INTO settings (`key`, `value`) VALUES
('site_name', 'FINOVA'),
('site_tagline_fr', 'Financement pour un avenir meilleur'),
('site_tagline_en', 'Funding for a better future'),
('site_tagline_es', 'Financiamiento para un futuro mejor'),
('site_tagline_ar', 'تمويل من أجل مستقبل أفضل'),
('site_tagline_pt', 'Financiamento para um futuro melhor'),
('contact_email', 'contact@finova.org'),
('contact_phone', '+1 (555) 000-0000'),
('contact_address', 'À compléter — ville, pays'),
('hero_color', '#0A2647'),
('accent_color', '#C9A84C'),
('total_funded', '0'),
('total_organizations', '0'),
('total_countries', '0');

-- Programme exemple
INSERT INTO programs (slug, is_active, max_amount, min_amount, currency, target_sector) VALUES
('social-impact-2025', 1, 500000.00, 5000.00, 'USD', 'Social, Éducation, Santé'),
('green-future-2025', 1, 250000.00, 10000.00, 'USD', 'Environnement, Agriculture durable'),
('digital-africa-2025', 1, 150000.00, 3000.00, 'USD', 'Technologie, Numérique, Innovation');

INSERT INTO program_translations (program_id, lang, title, description, objectives, eligibility_criteria) VALUES
(1, 'fr', 'Impact Social 2025', 'Programme de financement non remboursable destiné aux organisations œuvrant pour l''amélioration des conditions sociales, éducatives et sanitaires des communautés vulnérables.', 'Soutenir des projets à fort impact social mesurable dans les pays en développement.', 'ONG, associations et coopératives légalement enregistrées, actives depuis au moins 2 ans.'),
(1, 'en', 'Social Impact 2025', 'Non-repayable funding program for organizations working to improve social, educational and health conditions in vulnerable communities.', 'Support high social-impact projects in developing countries.', 'Legally registered NGOs, associations and cooperatives, active for at least 2 years.'),
(2, 'fr', 'Avenir Vert 2025', 'Programme dédié aux initiatives environnementales et agricoles durables pour lutter contre le changement climatique et promouvoir la sécurité alimentaire.', 'Financer des projets agro-écologiques, de reforestation et d''énergie renouvelable.', 'Toute organisation avec un projet ayant un impact environnemental démontrable.'),
(2, 'en', 'Green Future 2025', 'Program dedicated to sustainable environmental and agricultural initiatives to combat climate change and promote food security.', 'Fund agro-ecological, reforestation and renewable energy projects.', 'Any organization with a project with a demonstrable environmental impact.'),
(3, 'fr', 'Digital Afrique 2025', 'Programme de subvention pour accélérer la transformation numérique et l''innovation technologique au service des populations africaines.', 'Développer l''accès aux outils numériques et soutenir les startups à impact social.', 'Startups, PME et organisations dont le siège est en Afrique ou au service de populations africaines.');

-- FAQ
INSERT INTO faq (lang, question, answer, sort_order) VALUES
('fr', 'Ce financement est-il vraiment non remboursable ?', 'Oui, 100%. Nos programmes sont des subventions (grants) et dons directs. Il ne s''agit pas de prêts. Aucun remboursement ne sera jamais demandé.', 1),
('fr', 'Qui peut postuler ?', 'Toute organisation à but non lucratif, ONG, association, coopérative ou PME sociale légalement enregistrée peut soumettre une candidature, quel que soit le pays.', 2),
('fr', 'Quel est le délai de traitement ?', 'Les candidatures sont examinées sous 30 à 60 jours ouvrables. Vous recevrez une notification par email à chaque étape.', 3),
('fr', 'Quels documents sont requis ?', 'Statuts juridiques, attestation d''enregistrement, plan de projet détaillé, budget prévisionnel et rapport d''activité récent (si disponible).', 4),
('en', 'Is this funding truly non-repayable ?', 'Yes, 100%. Our programs are grants and direct donations. These are not loans. No repayment will ever be required.', 1),
('en', 'Who can apply ?', 'Any non-profit organization, NGO, association, cooperative or social enterprise legally registered can submit an application, regardless of country.', 2),
('en', 'What is the processing time ?', 'Applications are reviewed within 30 to 60 business days. You will receive an email notification at each step.', 3),
('en', 'What documents are required ?', 'Legal statutes, registration certificate, detailed project plan, projected budget and recent activity report (if available).', 4);
