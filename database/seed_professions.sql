-- Seed professions for ALL programs (safe to re-run)
-- This file inserts program-linked profession rows only if they don't already exist
-- Run inside your DB:
--   USE plp_tracer;
--   SOURCE C:/xampp/htdocs/plp-alumni-tracer/database/seed_professions.sql;

USE plp_tracer;

-- Helper pattern:
-- INSERT INTO professions (program_id, title, avg_salary, description)
-- SELECT ?, ?, ?, ?
-- WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = ? AND title = ?);

/* Program 1: Information Technology */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 1, 'Software Engineer', '₱45,000 - ₱90,000/mo', 'Builds and maintains software applications and systems.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 1 AND title = 'Software Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 1, 'Network Administrator', '₱35,000 - ₱70,000/mo', 'Manages and maintains an organization’s networks and infrastructure.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 1 AND title = 'Network Administrator');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 1, 'IT Support Specialist', '₱25,000 - ₱45,000/mo', 'Provides technical support, troubleshooting, and user assistance.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 1 AND title = 'IT Support Specialist');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 1, 'Web Developer', '₱35,000 - ₱80,000/mo', 'Develops and maintains websites and web applications.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 1 AND title = 'Web Developer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 1, 'Cybersecurity Analyst', '₱45,000 - ₱95,000/mo', 'Monitors, assesses, and strengthens security posture against threats.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 1 AND title = 'Cybersecurity Analyst');

/* Program 2: Computer Science */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 2, 'Data Analyst', '₱40,000 - ₱85,000/mo', 'Interprets data and turns it into information to support decisions.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 2 AND title = 'Data Analyst');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 2, 'Software Engineer', '₱45,000 - ₱90,000/mo', 'Builds scalable software systems with strong engineering practices.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 2 AND title = 'Software Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 2, 'QA / Test Engineer', '₱30,000 - ₱70,000/mo', 'Designs and runs tests to ensure software quality and reliability.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 2 AND title = 'QA / Test Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 2, 'Machine Learning Engineer', '₱55,000 - ₱120,000/mo', 'Builds and deploys ML models and data-driven systems.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 2 AND title = 'Machine Learning Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 2, 'Backend Developer', '₱45,000 - ₱100,000/mo', 'Develops APIs, databases, and server-side logic for applications.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 2 AND title = 'Backend Developer');

/* Program 3: Accountancy */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 3, 'Junior Accountant', '₱25,000 - ₱45,000/mo', 'Supports bookkeeping, reports, and general accounting tasks.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 3 AND title = 'Junior Accountant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 3, 'Audit Associate', '₱25,000 - ₱55,000/mo', 'Assists audit engagements, testing, and documentation.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 3 AND title = 'Audit Associate');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 3, 'Tax Associate', '₱25,000 - ₱55,000/mo', 'Prepares tax filings, compliance documents, and tax research.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 3 AND title = 'Tax Associate');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 3, 'Financial Analyst', '₱35,000 - ₱80,000/mo', 'Analyzes financial performance, budgeting, and forecasting.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 3 AND title = 'Financial Analyst');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 3, 'Bookkeeper', '₱20,000 - ₱40,000/mo', 'Maintains financial records, ledgers, and reconciliation.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 3 AND title = 'Bookkeeper');

/* Program 4: BSBA Marketing */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 4, 'Marketing Associate', '₱22,000 - ₱45,000/mo', 'Supports marketing campaigns, content, and coordination.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 4 AND title = 'Marketing Associate');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 4, 'Digital Marketing Specialist', '₱30,000 - ₱70,000/mo', 'Runs performance marketing, social media, and SEO/SEM initiatives.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 4 AND title = 'Digital Marketing Specialist');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 4, 'Sales Executive', '₱25,000 - ₱70,000/mo', 'Drives sales, manages leads, and builds client relationships.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 4 AND title = 'Sales Executive');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 4, 'Brand Coordinator', '₱25,000 - ₱60,000/mo', 'Supports brand activities, events, and brand consistency.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 4 AND title = 'Brand Coordinator');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 4, 'Market Research Analyst', '₱30,000 - ₱75,000/mo', 'Gathers and analyzes consumer/market insights for strategy.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 4 AND title = 'Market Research Analyst');

/* Program 5: Entrepreneurship */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 5, 'Business Development Associate', '₱25,000 - ₱60,000/mo', 'Identifies growth opportunities, partnerships, and new markets.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 5 AND title = 'Business Development Associate');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 5, 'Operations Coordinator', '₱22,000 - ₱50,000/mo', 'Supports daily operations, process improvement, and coordination.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 5 AND title = 'Operations Coordinator');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 5, 'Project Coordinator', '₱25,000 - ₱55,000/mo', 'Coordinates project tasks, timelines, and stakeholders.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 5 AND title = 'Project Coordinator');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 5, 'Product Associate', '₱30,000 - ₱70,000/mo', 'Assists product planning, research, and product execution.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 5 AND title = 'Product Associate');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 5, 'Small Business Owner', 'Varies', 'Starts and manages a business—planning, execution, and operations.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 5 AND title = 'Small Business Owner');

/* Program 6: Hospitality Management */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 6, 'Front Office Associate', '₱18,000 - ₱35,000/mo', 'Handles guest services, reservations, and front desk operations.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 6 AND title = 'Front Office Associate');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 6, 'Hotel Operations Supervisor', '₱28,000 - ₱55,000/mo', 'Supervises daily hotel operations and service standards.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 6 AND title = 'Hotel Operations Supervisor');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 6, 'Events Coordinator', '₱22,000 - ₱50,000/mo', 'Plans and coordinates events, logistics, and vendor management.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 6 AND title = 'Events Coordinator');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 6, 'Food & Beverage Supervisor', '₱22,000 - ₱45,000/mo', 'Supervises F&B service, staff, and customer experience.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 6 AND title = 'Food & Beverage Supervisor');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 6, 'Guest Relations Officer', '₱22,000 - ₱45,000/mo', 'Improves guest satisfaction and handles guest concerns.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 6 AND title = 'Guest Relations Officer');

/* Program 7: Nursing */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 7, 'Registered Nurse', '₱30,000 - ₱60,000/mo', 'Provides and coordinates patient care in clinical settings.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 7 AND title = 'Registered Nurse');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 7, 'Company Nurse', '₱25,000 - ₱50,000/mo', 'Provides workplace health services and first aid support.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 7 AND title = 'Company Nurse');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 7, 'Clinical Nurse (Ward)', '₱30,000 - ₱65,000/mo', 'Delivers bedside care and manages patient monitoring.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 7 AND title = 'Clinical Nurse (Ward)');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 7, 'Public Health Nurse', '₱28,000 - ₱55,000/mo', 'Supports community health programs and preventive care.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 7 AND title = 'Public Health Nurse');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 7, 'Nursing Assistant', '₱18,000 - ₱35,000/mo', 'Assists nurses with patient care and basic clinical tasks.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 7 AND title = 'Nursing Assistant');

/* Program 8: Electronics & Communications Engineering */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 8, 'Junior Electronics Engineer', '₱28,000 - ₱60,000/mo', 'Assists design, testing, and maintenance of electronic systems.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 8 AND title = 'Junior Electronics Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 8, 'Telecommunications Engineer', '₱35,000 - ₱80,000/mo', 'Works on telecom networks, transmission systems, and connectivity.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 8 AND title = 'Telecommunications Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 8, 'Network Field Engineer', '₱30,000 - ₱70,000/mo', 'Installs and troubleshoots network and communication equipment.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 8 AND title = 'Network Field Engineer');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 8, 'RF Engineer (Entry Level)', '₱35,000 - ₱85,000/mo', 'Supports RF planning, testing, and optimization tasks.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 8 AND title = 'RF Engineer (Entry Level)');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 8, 'Electronics Technician', '₱20,000 - ₱45,000/mo', 'Maintains and repairs electronics equipment and devices.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 8 AND title = 'Electronics Technician');

/* Program 9: Secondary Education (English) */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 9, 'Secondary School Teacher (English)', '₱22,000 - ₱40,000/mo', 'Teaches English and supports student learning outcomes.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 9 AND title = 'Secondary School Teacher (English)');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 9, 'ESL Teacher', '₱20,000 - ₱45,000/mo', 'Teaches English as a second language in schools or training centers.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 9 AND title = 'ESL Teacher');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 9, 'Academic Tutor', '₱15,000 - ₱35,000/mo', 'Provides tutoring support and learning reinforcement.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 9 AND title = 'Academic Tutor');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 9, 'Curriculum Assistant', '₱20,000 - ₱40,000/mo', 'Assists in curriculum planning, materials, and learning design.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 9 AND title = 'Curriculum Assistant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 9, 'Content Writer (Education)', '₱20,000 - ₱45,000/mo', 'Creates educational content, modules, and learning materials.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 9 AND title = 'Content Writer (Education)');

/* Program 10: Secondary Education (Mathematics) */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 10, 'Secondary School Teacher (Mathematics)', '₱22,000 - ₱40,000/mo', 'Teaches mathematics and supports learner competency.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 10 AND title = 'Secondary School Teacher (Mathematics)');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 10, 'Math Tutor', '₱15,000 - ₱35,000/mo', 'Provides tutoring for math subjects and exam preparation.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 10 AND title = 'Math Tutor');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 10, 'Academic Tutor', '₱15,000 - ₱35,000/mo', 'Provides tutoring support and learning reinforcement.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 10 AND title = 'Academic Tutor');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 10, 'Curriculum Assistant', '₱20,000 - ₱40,000/mo', 'Assists in curriculum planning, assessment, and materials.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 10 AND title = 'Curriculum Assistant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 10, 'Education Data Assistant', '₱22,000 - ₱45,000/mo', 'Supports reporting, grading analytics, and assessment data handling.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 10 AND title = 'Education Data Assistant');

/* Program 11: Secondary Education (Filipino) */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 11, 'Secondary School Teacher (Filipino)', '₱22,000 - ₱40,000/mo', 'Teaches Filipino language and literature.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 11 AND title = 'Secondary School Teacher (Filipino)');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 11, 'Academic Tutor', '₱15,000 - ₱35,000/mo', 'Provides tutoring support and learning reinforcement.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 11 AND title = 'Academic Tutor');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 11, 'Curriculum Assistant', '₱20,000 - ₱40,000/mo', 'Assists in curriculum planning, materials, and learning design.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 11 AND title = 'Curriculum Assistant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 11, 'Content Writer (Education)', '₱20,000 - ₱45,000/mo', 'Creates educational content, modules, and learning materials.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 11 AND title = 'Content Writer (Education)');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 11, 'Training Facilitator', '₱22,000 - ₱50,000/mo', 'Facilitates training sessions and develops learning activities.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 11 AND title = 'Training Facilitator');

/* Program 12: Elementary Education */
INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 12, 'Elementary School Teacher', '₱22,000 - ₱40,000/mo', 'Teaches elementary learners across core subjects.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 12 AND title = 'Elementary School Teacher');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 12, 'Teaching Assistant', '₱15,000 - ₱30,000/mo', 'Assists teachers with classroom activities and learner support.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 12 AND title = 'Teaching Assistant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 12, 'Child Development Assistant', '₱18,000 - ₱35,000/mo', 'Supports child development programs and learning support.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 12 AND title = 'Child Development Assistant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 12, 'Curriculum Assistant', '₱20,000 - ₱40,000/mo', 'Assists in curriculum planning, learning materials, and evaluation.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 12 AND title = 'Curriculum Assistant');

INSERT INTO professions (program_id, title, avg_salary, description)
SELECT 12, 'Education Program Coordinator (Entry Level)', '₱25,000 - ₱50,000/mo', 'Coordinates learning programs and school initiatives.'
WHERE NOT EXISTS (SELECT 1 FROM professions WHERE program_id = 12 AND title = 'Education Program Coordinator (Entry Level)');




