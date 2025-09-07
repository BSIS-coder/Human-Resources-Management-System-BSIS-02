-- ===============================
-- CORE USER AND EMPLOYEE TABLES
-- ===============================

-- Create user table (Admin and HR only)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'hr') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create personal_information table
CREATE TABLE personal_information (
    personal_info_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Non-binary', 'Prefer not to say') NOT NULL,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NOT NULL,
    nationality VARCHAR(50) NOT NULL,
    tax_id VARCHAR(20),
    social_security_number VARCHAR(20),
    phone_number VARCHAR(20) NOT NULL,
    emergency_contact_name VARCHAR(100),
    emergency_contact_relationship VARCHAR(50),
    emergency_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create job_roles table
CREATE TABLE job_roles (
    job_role_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    department VARCHAR(50) NOT NULL,
    min_salary DECIMAL(10,2),
    max_salary DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee_profiles table (no manager references)
CREATE TABLE employee_profiles (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    personal_info_id INT UNIQUE,
    job_role_id INT,
    employee_number VARCHAR(20) NOT NULL UNIQUE,
    hire_date DATE NOT NULL,
    employment_status ENUM('Full-time', 'Part-time', 'Contract', 'Intern', 'Terminated') NOT NULL,
    current_salary DECIMAL(10,2) NOT NULL,
    work_email VARCHAR(100) UNIQUE,
    work_phone VARCHAR(20),
    location VARCHAR(100),
    remote_work BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personal_info_id) REFERENCES personal_information(personal_info_id) ON DELETE SET NULL,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE SET NULL
);

-- Create departments table (no manager references)
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employment_history table (no manager references)
CREATE TABLE employment_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    job_role_id INT,
    start_date DATE NOT NULL,
    end_date DATE,
    salary DECIMAL(10,2) NOT NULL,
    reason_for_change VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE SET NULL
);

-- Create document_management table
CREATE TABLE document_management (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    document_type ENUM('Contract', 'ID', 'Resume', 'Certificate', 'Performance Review', 'Other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE,
    document_status ENUM('Active', 'Expired', 'Pending Review') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- ===============================
-- PAYROLL MANAGEMENT
-- ===============================

-- Create salary structure table
CREATE TABLE salary_structures (
    salary_structure_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    allowances DECIMAL(10, 2) DEFAULT 0,
    deductions DECIMAL(10, 2) DEFAULT 0,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create payroll cycles table
CREATE TABLE payroll_cycles (
    payroll_cycle_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_name VARCHAR(50) NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    pay_date DATE NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create tax deductions table
CREATE TABLE tax_deductions (
    tax_deduction_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    tax_type VARCHAR(50) NOT NULL,
    tax_percentage DECIMAL(5, 2),
    tax_amount DECIMAL(10, 2),
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create statutory deductions table
CREATE TABLE statutory_deductions (
    statutory_deduction_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    deduction_type VARCHAR(50) NOT NULL,
    deduction_amount DECIMAL(10, 2) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create benefits plans table
CREATE TABLE benefits_plans (
    benefit_plan_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    plan_type VARCHAR(50) NOT NULL,
    description TEXT,
    eligibility_criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee benefits table
CREATE TABLE employee_benefits (
    benefit_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    benefit_plan_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    benefit_amount DECIMAL(10, 2),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (benefit_plan_id) REFERENCES benefits_plans(benefit_plan_id) ON DELETE CASCADE
);

-- Create bonus payments table
CREATE TABLE bonus_payments (
    bonus_payment_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    bonus_type VARCHAR(50) NOT NULL,
    bonus_amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payroll_cycle_id INT,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_cycle_id) REFERENCES payroll_cycles(payroll_cycle_id) ON DELETE SET NULL
);

-- Create compensation packages table
CREATE TABLE compensation_packages (
    compensation_package_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    base_salary DECIMAL(10, 2) NOT NULL,
    variable_pay DECIMAL(10, 2) DEFAULT 0,
    benefits_summary TEXT,
    total_compensation DECIMAL(10, 2) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create payroll transactions table
CREATE TABLE payroll_transactions (
    payroll_transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    payroll_cycle_id INT NOT NULL,
    gross_pay DECIMAL(10, 2) NOT NULL,
    tax_deductions DECIMAL(10, 2) DEFAULT 0,
    statutory_deductions DECIMAL(10, 2) DEFAULT 0,
    other_deductions DECIMAL(10, 2) DEFAULT 0,
    net_pay DECIMAL(10, 2) NOT NULL,
    processed_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processed', 'Paid', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_cycle_id) REFERENCES payroll_cycles(payroll_cycle_id) ON DELETE CASCADE
);

-- Create payment disbursements table
CREATE TABLE payment_disbursements (
    payment_disbursement_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_transaction_id INT NOT NULL,
    employee_id INT NOT NULL,
    payment_method ENUM('Bank Transfer', 'Check', 'Cash', 'Other') NOT NULL,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    payment_amount DECIMAL(10, 2) NOT NULL,
    disbursement_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processed', 'Failed') DEFAULT 'Pending',
    reference_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_transaction_id) REFERENCES payroll_transactions(payroll_transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create payslips table
CREATE TABLE payslips (
    payslip_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_transaction_id INT NOT NULL,
    employee_id INT NOT NULL,
    payslip_url VARCHAR(255),
    generated_date DATETIME NOT NULL,
    status ENUM('Generated', 'Sent', 'Viewed') DEFAULT 'Generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_transaction_id) REFERENCES payroll_transactions(payroll_transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- ===============================
-- PERFORMANCE MANAGEMENT
-- ===============================

-- Create competencies table
CREATE TABLE competencies (
    competency_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee competencies table (no assessor reference)
CREATE TABLE employee_competencies (
    employee_id INT NOT NULL,
    competency_id INT NOT NULL,
    rating INT NOT NULL,
    assessment_date DATE NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (employee_id, competency_id, assessment_date),
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(competency_id) ON DELETE CASCADE
);

-- Create performance review cycles table
CREATE TABLE performance_review_cycles (
    cycle_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Upcoming', 'In Progress', 'Completed') DEFAULT 'Upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create performance reviews table (no reviewer references)
CREATE TABLE performance_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    cycle_id INT NOT NULL,
    review_date DATE NOT NULL,
    overall_rating DECIMAL(3,2) NOT NULL,
    strengths TEXT,
    areas_of_improvement TEXT,
    comments TEXT,
    status ENUM('Draft', 'Submitted', 'Acknowledged', 'Finalized') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (cycle_id) REFERENCES performance_review_cycles(cycle_id) ON DELETE CASCADE
);

-- Create goals table (no supervisor reference)
CREATE TABLE goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Not Started', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Not Started',
    progress DECIMAL(5,2) DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create goal updates table (no creator reference)
CREATE TABLE goal_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    update_date DATE NOT NULL,
    progress DECIMAL(5,2) NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(goal_id) ON DELETE CASCADE
);

-- Create performance metrics table (no recorder reference)
CREATE TABLE performance_metrics (
    metric_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    recorded_date DATE NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create development plans table (no coach reference)
CREATE TABLE development_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    plan_description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Draft', 'Active', 'Completed', 'Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create development activities table
CREATE TABLE development_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    activity_name VARCHAR(100) NOT NULL,
    activity_type ENUM('Training', 'Mentoring', 'Project', 'Education', 'Other') NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES development_plans(plan_id) ON DELETE CASCADE
);

-- ===============================
-- LEAVE AND ATTENDANCE MANAGEMENT
-- ===============================

-- Create public holidays table
CREATE TABLE public_holidays (
    holiday_id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE UNIQUE NOT NULL,
    holiday_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create leave types table
CREATE TABLE leave_types (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(50) NOT NULL,
    description TEXT,
    paid BOOLEAN DEFAULT TRUE,
    default_days DECIMAL(5,2) DEFAULT 0,
    carry_forward BOOLEAN DEFAULT FALSE,
    max_carry_forward_days DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create leave balances table
CREATE TABLE leave_balances (
    balance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    year YEAR NOT NULL,
    total_leaves DECIMAL(5,2) NOT NULL,
    leaves_taken DECIMAL(5,2) DEFAULT 0,
    leaves_pending DECIMAL(5,2) DEFAULT 0,
    leaves_remaining DECIMAL(5,2),
    last_updated DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id) ON DELETE CASCADE
);

-- Create leave requests table (no approver reference)
CREATE TABLE leave_requests (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(5,2) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    applied_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_on DATETIME,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id) ON DELETE CASCADE
);

-- Create shifts table
CREATE TABLE shifts (
    shift_id INT AUTO_INCREMENT PRIMARY KEY,
    shift_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee shifts table
CREATE TABLE employee_shifts (
    employee_shift_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    shift_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    is_overtime BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE CASCADE
);

-- Create attendance table
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    clock_in DATETIME,
    clock_out DATETIME,
    status ENUM('Present', 'Absent', 'Late', 'Half Day', 'On Leave') NOT NULL,
    working_hours DECIMAL(5,2),
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create attendance_summary table
CREATE TABLE attendance_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year YEAR NOT NULL,
    total_present INT DEFAULT 0,
    total_absent INT DEFAULT 0,
    total_late INT DEFAULT 0,
    total_leave INT DEFAULT 0,
    total_working_hours DECIMAL(7,2) DEFAULT 0,
    total_overtime_hours DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    UNIQUE KEY (employee_id, month, year)
);

-- ===============================
-- EXIT MANAGEMENT
-- ===============================

-- Create exits table (no initiator/approver references)
CREATE TABLE exits (
    exit_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    exit_type ENUM('Resignation', 'Termination', 'Retirement', 'End of Contract', 'Other') NOT NULL,
    exit_reason TEXT,
    notice_date DATE NOT NULL,
    exit_date DATE NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create exit checklist table (no completion reference)
CREATE TABLE exit_checklist (
    checklist_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    responsible_department VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Completed', 'Not Applicable') DEFAULT 'Pending',
    completed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE
);

-- Create exit interviews table (no interviewer reference)
CREATE TABLE exit_interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    interview_date DATE NOT NULL,
    feedback TEXT,
    improvement_suggestions TEXT,
    reason_for_leaving TEXT,
    would_recommend BOOLEAN,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create knowledge transfers table (no successor/completion references)
CREATE TABLE knowledge_transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    handover_details TEXT,
    start_date DATE,
    completion_date DATE,
    status ENUM('Not Started', 'In Progress', 'Completed', 'N/A') DEFAULT 'Not Started',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create settlements table (no processor reference)
CREATE TABLE settlements (
    settlement_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    last_working_day DATE NOT NULL,
    final_salary DECIMAL(10,2) NOT NULL,
    severance_pay DECIMAL(10,2) DEFAULT 0,
    unused_leave_payout DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    final_settlement_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE,
    payment_method VARCHAR(50),
    status ENUM('Pending', 'Processing', 'Completed') DEFAULT 'Pending',
    processed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create exit documents table (no uploader reference)
CREATE TABLE exit_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_url VARCHAR(255) NOT NULL,
    uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create post exit surveys table
CREATE TABLE post_exit_surveys (
    survey_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    exit_id INT NOT NULL,
    survey_date DATE NOT NULL,
    survey_response TEXT,
    satisfaction_rating INT,
    submitted_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE
);

-- ===============================
-- RECRUITMENT MANAGEMENT
-- ===============================

-- Create job_openings table (no hiring manager reference)
CREATE TABLE job_openings (
    job_opening_id INT AUTO_INCREMENT PRIMARY KEY,
    job_role_id INT NOT NULL,
    department_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    responsibilities TEXT NOT NULL,
    location VARCHAR(100),
    employment_type ENUM('Full-time', 'Part-time', 'Contract', 'Temporary', 'Internship') NOT NULL,
    experience_level VARCHAR(50),
    education_requirements TEXT,
    salary_range_min DECIMAL(10,2),
    salary_range_max DECIMAL(10,2),
    vacancy_count INT DEFAULT 1,
    posting_date DATE NOT NULL,
    closing_date DATE,
    status ENUM('Draft', 'Open', 'On Hold', 'Closed', 'Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
);

-- Create candidates table
CREATE TABLE candidates (
    candidate_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    resume_data LONGBLOB,
    resume_filename VARCHAR(255),
    photo_data LONGBLOB,
    photo_filename VARCHAR(255),
    source VARCHAR(100),
    current_position VARCHAR(100),
    current_company VARCHAR(100),
    notice_period VARCHAR(50),
    expected_salary DECIMAL(10,2),
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create job_applications table
CREATE TABLE job_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_opening_id INT NOT NULL,
    candidate_id INT NOT NULL,
    application_date DATETIME NOT NULL,
    status ENUM('Applied', 'Screening', 'Interview', 'Assessment', 'Onboarding', 'Offer', 'Hired', 'Rejected') DEFAULT 'Applied',
    notes TEXT,
    assessment_scores JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE
);

-- Create interview_stages table
CREATE TABLE interview_stages (
    stage_id INT AUTO_INCREMENT PRIMARY KEY,
    job_opening_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    stage_order INT NOT NULL,
    description TEXT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE
);

-- Create interviews table (no interviewer reference)
CREATE TABLE interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    stage_id INT NOT NULL,
    schedule_date DATETIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    location VARCHAR(255),
    interview_type ENUM('In-person', 'Phone', 'Video Call', 'Technical Assessment') NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Rescheduled', 'Cancelled') DEFAULT 'Scheduled',
    feedback TEXT,
    rating DECIMAL(3,2),
    recommendation ENUM('Strong Yes', 'Yes', 'Maybe', 'No', 'Strong No'),
    completed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES interview_stages(stage_id) ON DELETE CASCADE
);

-- Create job_offers table (supports both hiring and promotions)
CREATE TABLE job_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NULL,
    employee_id INT NULL,
    job_opening_id INT NOT NULL,
    offer_type ENUM('hire', 'promotion') NOT NULL,
    salary_offered DECIMAL(10,2) NOT NULL,
    benefits TEXT,
    start_date DATE,
    offer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_date TIMESTAMP NULL,
    status ENUM('Pending', 'Accepted', 'Rejected', 'Withdrawn') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE
);

-- Create recruitment_analytics table
CREATE TABLE recruitment_analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    job_opening_id INT NOT NULL,
    total_applications INT DEFAULT 0,
    applications_per_day DECIMAL(5,2) DEFAULT 0,
    average_processing_time INT DEFAULT 0 COMMENT 'In days',
    average_time_to_hire INT DEFAULT 0 COMMENT 'In days',
    offer_acceptance_rate DECIMAL(5,2) DEFAULT 0,
    recruitment_source_breakdown JSON,
    cost_per_hire DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE
);

-- Create onboarding_tasks table
CREATE TABLE onboarding_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT,
    task_type ENUM('Administrative', 'Equipment', 'Training', 'Introduction', 'Documentation', 'Other') NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    default_due_days INT DEFAULT 7 COMMENT 'Days after joining date',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
);

-- Create onboarding table for candidates
CREATE TABLE onboarding (
    onboarding_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    candidate_id INT NOT NULL,
    department_id INT NOT NULL,
    start_date DATE NOT NULL,
    expected_completion_date DATE NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
);

-- Create onboarding_task_progress table for tracking individual task completion
CREATE TABLE onboarding_task_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT NOT NULL,
    task_id INT NOT NULL,
    status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
    completed_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (onboarding_id) REFERENCES onboarding(onboarding_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES onboarding_tasks(task_id) ON DELETE CASCADE
);

-- Create employee_onboarding table (no hiring manager reference)
CREATE TABLE employee_onboarding (
    onboarding_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    expected_completion_date DATE NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create employee_onboarding_tasks table (no assignment/completion references)
CREATE TABLE employee_onboarding_tasks (
    employee_task_id INT AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT NOT NULL,
    task_id INT NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Not Started', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Not Started',
    completion_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (onboarding_id) REFERENCES employee_onboarding(onboarding_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES onboarding_tasks(task_id) ON DELETE CASCADE
);

-- ===============================
-- TRAINING AND DEVELOPMENT
-- ===============================

-- Create training_courses table
CREATE TABLE training_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    delivery_method ENUM('Online', 'Classroom', 'Workshop', 'Self-paced', 'Hybrid') NOT NULL,
    duration INT COMMENT 'Duration in hours',
    max_participants INT,
    prerequisites TEXT,
    materials_url VARCHAR(255),
    status ENUM('Active', 'Inactive', 'In Development') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create trainers table (no employee reference)
CREATE TABLE trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(255),
    bio TEXT,
    is_internal BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create training_sessions table
CREATE TABLE training_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    trainer_id INT NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255),
    capacity INT NOT NULL,
    cost_per_participant DECIMAL(10,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES training_courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE
);

-- Create training_enrollments table (no nominator reference)
CREATE TABLE training_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    employee_id INT NOT NULL,
    enrollment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Enrolled', 'Completed', 'Dropped', 'Failed', 'Waitlisted') DEFAULT 'Enrolled',
    completion_date DATE,
    score DECIMAL(5,2),
    feedback TEXT,
    certificate_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES training_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create learning_resources table
CREATE TABLE learning_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    resource_name VARCHAR(255) NOT NULL,
    resource_type ENUM('Book', 'Online Course', 'Video', 'Article', 'Webinar', 'Podcast', 'Other') NOT NULL,
    description TEXT,
    resource_url VARCHAR(255),
    author VARCHAR(100),
    publication_date DATE,
    duration VARCHAR(50),
    tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee_resources table (no assigner reference)
CREATE TABLE employee_resources (
    employee_resource_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    resource_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    due_date DATE,
    completed_date DATE,
    status ENUM('Assigned', 'In Progress', 'Completed', 'Overdue') DEFAULT 'Assigned',
    rating INT,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES learning_resources(resource_id) ON DELETE CASCADE
);

-- Create skill_matrix table
CREATE TABLE skill_matrix (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee_skills table (no assessor reference)
CREATE TABLE employee_skills (
    employee_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
    assessed_date DATE NOT NULL,
    certification_url VARCHAR(255),
    expiry_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill_matrix(skill_id) ON DELETE CASCADE
);

-- Create training_needs_assessment table (no assessor reference)
CREATE TABLE training_needs_assessment (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    assessment_date DATE NOT NULL,
    skills_gap TEXT,
    recommended_trainings TEXT,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Identified', 'In Progress', 'Completed') DEFAULT 'Identified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create career_paths table
CREATE TABLE career_paths (
    path_id INT AUTO_INCREMENT PRIMARY KEY,
    path_name VARCHAR(100) NOT NULL,
    description TEXT,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
);

-- Create career_path_stages table
CREATE TABLE career_path_stages (
    stage_id INT AUTO_INCREMENT PRIMARY KEY,
    path_id INT NOT NULL,
    job_role_id INT NOT NULL,
    stage_order INT NOT NULL,
    minimum_time_in_role INT COMMENT 'In months',
    required_skills TEXT,
    required_experience TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE CASCADE
);

-- Create employee_career_paths table (no mentor reference)
CREATE TABLE employee_career_paths (
    employee_path_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    path_id INT NOT NULL,
    current_stage_id INT NOT NULL,
    start_date DATE NOT NULL,
    target_completion_date DATE,
    status ENUM('Active', 'Completed', 'On Hold', 'Abandoned') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
    FOREIGN KEY (current_stage_id) REFERENCES career_path_stages(stage_id) ON DELETE CASCADE
);

-- ===============================
-- SAMPLE DATA FOR TESTING
-- ===============================

-- Insert sample data for users (Admin and HR only)
INSERT INTO users (username, password, email, role) VALUES
    ('admin', 'admin123', 'admin@company.com', 'admin'),
    ('hr_manager', 'hr123', 'hr@company.com', 'hr');

-- Insert sample personal information for municipal employees
INSERT INTO personal_information (first_name, last_name, date_of_birth, gender, marital_status, nationality, tax_id, social_security_number, phone_number, emergency_contact_name, emergency_contact_relationship, emergency_contact_phone) VALUES
('Maria', 'Santos', '1985-03-12', 'Female', 'Married', 'Filipino', '123-45-6789', '123456789', '0917-123-4567', 'Carlos Santos', 'Spouse', '0917-567-8901'),
('Roberto', 'Cruz', '1978-07-20', 'Male', 'Married', 'Filipino', '234-56-7890', '234567890', '0917-234-5678', 'Elena Cruz', 'Spouse', '0917-678-9012'),
('Jennifer', 'Reyes', '1988-11-08', 'Female', 'Single', 'Filipino', '345-67-8901', '345678901', '0917-345-6789', 'Mark Reyes', 'Brother', '0917-789-0123'),
('Antonio', 'Garcia', '1975-01-25', 'Male', 'Married', 'Filipino', '456-78-9012', '456789012', '0917-456-7890', 'Rosa Garcia', 'Spouse', '0917-890-1234'),
('Lisa', 'Mendoza', '1982-09-14', 'Female', 'Divorced', 'Filipino', '567-89-0123', '567890123', '0917-567-8901', 'John Mendoza', 'Father', '0917-901-2345'),
('Michael', 'Torres', '1980-06-03', 'Male', 'Married', 'Filipino', '678-90-1234', '678901234', '0917-678-9012', 'Anna Torres', 'Spouse', '0917-012-3456'),
('Carmen', 'Dela Cruz', '1987-12-18', 'Female', 'Single', 'Filipino', '789-01-2345', '789012345', '0917-789-0123', 'Pedro Dela Cruz', 'Father', '0917-123-4567'),
('Ricardo', 'Villanueva', '1970-04-07', 'Male', 'Married', 'Filipino', '890-12-3456', '890123456', '0917-890-1234', 'Diana Villanueva', 'Spouse', '0917-234-5678'),
('Sandra', 'Pascual', '1984-08-29', 'Female', 'Married', 'Filipino', '901-23-4567', '901234567', '0917-901-2345', 'Luis Pascual', 'Spouse', '0917-345-6789'),
('Jose', 'Ramos', '1972-05-15', 'Male', 'Married', 'Filipino', '012-34-5678', '012345678', '0917-012-3456', 'Teresa Ramos', 'Spouse', '0917-456-7890'),
('Ana', 'Morales', '1986-10-30', 'Female', 'Single', 'Filipino', '123-56-7890', '123567890', '0917-135-7890', 'Maria Morales', 'Mother', '0917-579-0123'),
('Pablo', 'Fernandez', '1979-02-22', 'Male', 'Married', 'Filipino', '234-67-8901', '234678901', '0917-246-7890', 'Carmen Fernandez', 'Spouse', '0917-680-1234'),
('Grace', 'Lopez', '1983-09-07', 'Female', 'Married', 'Filipino', '345-78-9012', '345789012', '0917-357-8901', 'David Lopez', 'Spouse', '0917-791-2345'),
('Eduardo', 'Hernandez', '1977-12-03', 'Male', 'Married', 'Filipino', '456-89-0123', '456890123', '0917-468-9012', 'Sofia Hernandez', 'Spouse', '0917-802-3456'),
('Rosario', 'Gonzales', '1989-06-28', 'Female', 'Single', 'Filipino', '567-90-1234', '567901234', '0917-579-0123', 'Miguel Gonzales', 'Father', '0917-913-4567');

-- Insert sample data for departments
INSERT INTO departments (department_name, description, location) VALUES
('Office of the Mayor', 'Executive office responsible for municipal governance and administration', 'City Hall - 2nd Floor'),
('Sangguniang Bayan', 'Municipal legislative body responsible for enacting local ordinances', 'City Hall - Session Hall'),
('Municipal Treasurer''s Office', 'Handles municipal revenue collection, treasury operations, and financial management', 'City Hall - 1st Floor'),
('Municipal Budget Office', 'Responsible for budget preparation, monitoring, and financial planning', 'City Hall - 1st Floor'),
('Municipal Accountant''s Office', 'Manages municipal accounting, bookkeeping, and financial reporting', 'City Hall - 1st Floor'),
('Municipal Planning & Development Office', 'Handles municipal planning, development programs, and project management', 'City Hall - 3rd Floor'),
('Municipal Engineer''s Office', 'Oversees infrastructure projects, public works, and engineering services', 'Engineering Building'),
('Municipal Civil Registrar''s Office', 'Manages civil registration services and vital statistics', 'City Hall - Ground Floor'),
('Municipal Health Office', 'Provides public health services and healthcare programs', 'Health Center Building'),
('Municipal Social Welfare & Development Office', 'Administers social services and community development programs', 'Social Services Building'),
('Municipal Agriculture Office', 'Supports agricultural development and provides farming assistance', 'Agriculture Extension Office'),
('Municipal Assessor''s Office', 'Conducts property assessment and real property taxation', 'City Hall - Ground Floor'),
('Municipal Human Resource & Administrative Office', 'Manages personnel administration and human resources', 'City Hall - 2nd Floor'),
('Municipal Disaster Risk Reduction & Management Office', 'Coordinates disaster preparedness and emergency response', 'Emergency Operations Center'),
('General Services Office', 'Provides general administrative support and facility management', 'City Hall - Basement');

-- Insert sample data for job_roles
INSERT INTO job_roles (title, description, department, min_salary, max_salary) VALUES
-- Elected Officials (Higher salary grades)
('Mayor', 'Chief executive of the municipality responsible for overall governance', 'Office of the Mayor', 80000.00, 120000.00),
('Vice Mayor', 'Presiding officer of Sangguniang Bayan and assistant to the Mayor', 'Sangguniang Bayan', 70000.00, 100000.00),
('Councilor', 'Member of the municipal legislative body', 'Sangguniang Bayan', 60000.00, 85000.00),

-- Department Heads / Appointed Officials
('Municipal Treasurer', 'Head of treasury operations and revenue collection', 'Municipal Treasurer''s Office', 55000.00, 75000.00),
('Municipal Budget Officer', 'Responsible for municipal budget preparation and monitoring', 'Municipal Budget Office', 50000.00, 70000.00),
('Municipal Accountant', 'Chief accountant responsible for municipal financial records', 'Municipal Accountant''s Office', 50000.00, 70000.00),
('Municipal Planning & Development Coordinator', 'Head of municipal planning and development programs', 'Municipal Planning & Development Office', 55000.00, 75000.00),
('Municipal Engineer', 'Chief engineer overseeing infrastructure and public works', 'Municipal Engineer''s Office', 60000.00, 85000.00),
('Municipal Civil Registrar', 'Head of civil registration services', 'Municipal Civil Registrar''s Office', 45000.00, 65000.00),
('Municipal Health Officer', 'Chief medical officer and head of health services', 'Municipal Health Office', 70000.00, 95000.00),
('Municipal Social Welfare Officer', 'Head of social welfare and development programs', 'Municipal Social Welfare & Development Office', 50000.00, 70000.00),
('Municipal Agriculturist', 'Agricultural development officer and extension coordinator', 'Municipal Agriculture Office', 50000.00, 70000.00),
('Municipal Assessor', 'Head of property assessment and real property taxation', 'Municipal Assessor''s Office', 50000.00, 70000.00),
('Municipal HR Officer', 'Head of human resources and personnel administration', 'Municipal Human Resource & Administrative Office', 50000.00, 70000.00),
('MDRRM Officer', 'Disaster risk reduction and management coordinator', 'Municipal Disaster Risk Reduction & Management Office', 45000.00, 65000.00),
('General Services Officer', 'Head of general services and facility management', 'General Services Office', 40000.00, 60000.00),

-- Technical & Professional Staff
('Nurse', 'Provides nursing services and healthcare support', 'Municipal Health Office', 35000.00, 50000.00),
('Midwife', 'Provides maternal and child health services', 'Municipal Health Office', 30000.00, 45000.00),
('Sanitary Inspector', 'Conducts health and sanitation inspections', 'Municipal Health Office', 28000.00, 40000.00),
('Social Worker', 'Provides social services and community assistance', 'Municipal Social Welfare & Development Office', 35000.00, 50000.00),
('Agricultural Technician', 'Provides technical support for agricultural programs', 'Municipal Agriculture Office', 28000.00, 40000.00),
('Civil Engineer', 'Designs and supervises infrastructure projects', 'Municipal Engineer''s Office', 45000.00, 65000.00),
('CAD Operator', 'Creates technical drawings and engineering plans', 'Municipal Engineer''s Office', 30000.00, 45000.00),
('Building Inspector', 'Inspects construction projects for code compliance', 'Municipal Engineer''s Office', 35000.00, 50000.00),
('Budget Analyst', 'Analyzes budget data and prepares financial reports', 'Municipal Budget Office', 35000.00, 50000.00),
('Accounting Staff', 'Handles bookkeeping and accounting transactions', 'Municipal Accountant''s Office', 25000.00, 38000.00),
('Planning Staff', 'Assists in municipal planning and development activities', 'Municipal Planning & Development Office', 30000.00, 45000.00),

-- Administrative & Support Staff
('Administrative Aide', 'Provides administrative support to various departments', 'Municipal Human Resource & Administrative Office', 22000.00, 35000.00),
('Clerk', 'Handles clerical work and document processing', 'Municipal Civil Registrar''s Office', 20000.00, 32000.00),
('Cashier', 'Processes payments and financial transactions', 'Municipal Treasurer''s Office', 22000.00, 35000.00),
('Collection Officer', 'Collects municipal revenues and taxes', 'Municipal Treasurer''s Office', 25000.00, 38000.00),
('Property Custodian', 'Manages and maintains municipal property and assets', 'General Services Office', 22000.00, 35000.00),
('Maintenance Worker', 'Performs maintenance and repair work on municipal facilities', 'General Services Office', 18000.00, 28000.00),
('Utility Worker', 'Provides general utility and janitorial services', 'General Services Office', 16000.00, 25000.00),
('Driver', 'Operates municipal vehicles and provides transportation services', 'General Services Office', 20000.00, 32000.00),
('Security Personnel', 'Provides security services for municipal facilities', 'General Services Office', 18000.00, 28000.00),
('Legislative Staff', 'Provides secretarial support to Sangguniang Bayan', 'Sangguniang Bayan', 25000.00, 38000.00);

-- Create employees table for job offers system
CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    current_position VARCHAR(100),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample employees for promotions
INSERT INTO employees (first_name, last_name, email, current_position) VALUES
('Maria', 'Santos', 'maria.santos@municipality.gov.ph', 'Municipal Treasurer'),
('Roberto', 'Cruz', 'roberto.cruz@municipality.gov.ph', 'Municipal Engineer'),
('Jennifer', 'Reyes', 'jennifer.reyes@municipality.gov.ph', 'Nurse'),
('Antonio', 'Garcia', 'antonio.garcia@municipality.gov.ph', 'CAD Operator'),
('Lisa', 'Mendoza', 'lisa.mendoza@municipality.gov.ph', 'Social Worker'),
('Michael', 'Torres', 'michael.torres@municipality.gov.ph', 'Accounting Staff'),
('Carmen', 'Dela Cruz', 'carmen.delacruz@municipality.gov.ph', 'Clerk'),
('Ricardo', 'Villanueva', 'ricardo.villanueva@municipality.gov.ph', 'Maintenance Worker'),
('Sandra', 'Pascual', 'sandra.pascual@municipality.gov.ph', 'Cashier'),
('Jose', 'Ramos', 'jose.ramos@municipality.gov.ph', 'Collection Officer');

-- Insert sample job openings
INSERT INTO job_openings (job_role_id, department_id, title, description, requirements, responsibilities, location, employment_type, salary_range_min, salary_range_max, vacancy_count, posting_date, closing_date, status) VALUES
(17, 9, 'Municipal Nurse', 'Provide nursing services and healthcare support to municipal residents', 'Bachelor of Science in Nursing, Valid PRC License, At least 2 years experience', 'Provide nursing care, assist in medical procedures, health education', 'Municipal Health Office', 'Full-time', 35000.00, 50000.00, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Open'),
(26, 13, 'Administrative Aide', 'Provide administrative support to HR department', 'College graduate, Computer literate, Good communication skills', 'File management, data entry, assist in HR processes', 'City Hall - 2nd Floor', 'Full-time', 22000.00, 35000.00, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Open');

-- Insert default interview stages for the sample job openings
INSERT INTO interview_stages (job_opening_id, stage_name, stage_order, description, is_mandatory) VALUES
-- For Municipal Nurse position
(1, 'Initial Screening', 1, 'Review of application documents and basic qualifications', 1),
(1, 'Technical Interview', 2, 'Assessment of nursing knowledge and clinical skills', 1),
(1, 'Panel Interview', 3, 'Interview with department heads and HR', 1),
(1, 'Medical Examination', 4, 'Health assessment and fitness for duty', 1),

-- For Administrative Aide position
(2, 'Document Review', 1, 'Verification of educational background and requirements', 1),
(2, 'Skills Assessment', 2, 'Computer literacy and administrative skills test', 1),
(2, 'HR Interview', 3, 'Final interview with HR manager', 1);

-- Insert sample candidates for testing
INSERT INTO candidates (first_name, last_name, email, phone, address, source, current_position, expected_salary, resume_filename, photo_filename, email_verified) VALUES
('Anna', 'Rodriguez', 'anna.rodriguez@email.com', '0917-111-2222', '123 Main St, City', 'Job Portal', 'Staff Nurse', 40000.00, 'anna_rodriguez_resume.pdf', 'anna_rodriguez_photo.jpg', 1),
('Mark', 'Gonzales', 'mark.gonzales@email.com', '0917-333-4444', '456 Oak Ave, City', 'Walk-in', 'Administrative Assistant', 25000.00, 'mark_gonzales_resume.pdf', 'mark_gonzales_photo.jpg', 1),
('Sarah', 'Dela Rosa', 'sarah.delarosa@email.com', '0917-555-6666', '789 Pine St, City', 'Referral', 'Registered Nurse', 42000.00, 'sarah_delarosa_resume.pdf', 'sarah_delarosa_photo.jpg', 1);

-- Insert sample job applications
INSERT INTO job_applications (job_opening_id, candidate_id, application_date, status) VALUES
(1, 1, NOW(), 'Applied'),
(1, 3, NOW(), 'Applied'),
(2, 2, NOW(), 'Applied');

-- Insert sample data for employee_profiles
INSERT INTO employee_profiles (personal_info_id, job_role_id, employee_number, hire_date, employment_status, current_salary, work_email, work_phone, location, remote_work) VALUES
-- Department Heads and Key Officials
(1, 4, 'MUN001', '2019-07-01', 'Full-time', 65000.00, 'maria.santos@municipality.gov.ph', '034-123-0001', 'City Hall - 1st Floor', FALSE),
(2, 8, 'MUN002', '2018-06-15', 'Full-time', 75000.00, 'roberto.cruz@municipality.gov.ph', '034-123-0002', 'Engineering Building', FALSE),
(3, 17, 'MUN003', '2020-01-20', 'Full-time', 42000.00, 'jennifer.reyes@municipality.gov.ph', '034-123-0003', 'Municipal Health Office', FALSE),
(4, 21, 'MUN004', '2019-03-10', 'Full-time', 38000.00, 'antonio.garcia@municipality.gov.ph', '034-123-0004', 'Municipal Engineer''s Office', FALSE),
(5, 20, 'MUN005', '2021-09-05', 'Full-time', 45000.00, 'lisa.mendoza@municipality.gov.ph', '034-123-0005', 'Municipal Social Welfare & Development Office', FALSE),
(6, 25, 'MUN006', '2020-11-12', 'Full-time', 28000.00, 'michael.torres@municipality.gov.ph', '034-123-0006', 'Municipal Accountant''s Office', FALSE),
(7, 27, 'MUN007', '2022-02-28', 'Full-time', 30000.00, 'carmen.delacruz@municipality.gov.ph', '034-123-0007', 'Municipal Civil Registrar''s Office', FALSE),
(8, 32, 'MUN008', '2021-05-18', 'Full-time', 22000.00, 'ricardo.villanueva@municipality.gov.ph', '034-123-0008', 'General Services Office', FALSE),
(9, 28, 'MUN009', '2020-09-10', 'Full-time', 32000.00, 'sandra.pascual@municipality.gov.ph', '034-123-0009', 'Municipal Treasurer''s Office', FALSE),
(10, 29, 'MUN010', '2019-12-01', 'Full-time', 35000.00, 'jose.ramos@municipality.gov.ph', '034-123-0010', 'Municipal Treasurer''s Office', FALSE),
(11, 26, 'MUN011', '2022-04-15', 'Full-time', 28000.00, 'ana.morales@municipality.gov.ph', '034-123-0011', 'Municipal Human Resource & Administrative Office', FALSE),
(12, 19, 'MUN012', '2021-08-20', 'Full-time', 40000.00, 'pablo.fernandez@municipality.gov.ph', '034-123-0012', 'Municipal Agriculture Office', FALSE),
(13, 18, 'MUN013', '2020-06-30', 'Full-time', 42000.00, 'grace.lopez@municipality.gov.ph', '034-123-0013', 'Municipal Health Office', FALSE),
(14, 31, 'MUN014', '2022-01-10', 'Full-time', 25000.00, 'eduardo.hernandez@municipality.gov.ph', '034-123-0014', 'General Services Office', FALSE),
(15, 33, 'MUN015', '2021-11-05', 'Full-time', 24000.00, 'rosario.gonzales@municipality.gov.ph', '034-123-0015', 'General Services Office', FALSE);

-- Insert sample data for employment_history
INSERT INTO employment_history (employee_id, job_role_id, start_date, end_date, salary, reason_for_change) VALUES
-- Current positions
(1, 4, '2019-07-01', NULL, 65000.00, 'Appointed as Municipal Treasurer'),
(2, 8, '2018-06-15', NULL, 75000.00, 'Appointed as Municipal Engineer'),
(3, 17, '2020-01-20', NULL, 42000.00, 'Hired as Nurse'),
(4, 21, '2019-03-10', NULL, 38000.00, 'Hired as CAD Operator'),
(5, 20, '2021-09-05', NULL, 45000.00, 'Hired as Social Worker'),
(6, 25, '2020-11-12', NULL, 28000.00, 'Hired as Accounting Staff'),
(7, 27, '2022-02-28', NULL, 30000.00, 'Hired as Clerk'),
(8, 32, '2021-05-18', NULL, 22000.00, 'Hired as Maintenance Worker'),
(9, 28, '2020-09-10', NULL, 32000.00, 'Hired as Cashier'),
(10, 29, '2019-12-01', NULL, 35000.00, 'Hired as Collection Officer'),
(11, 26, '2022-04-15', NULL, 28000.00, 'Hired as Administrative Aide'),
(12, 19, '2021-08-20', NULL, 40000.00, 'Hired as Agricultural Technician'),
(13, 18, '2020-06-30', NULL, 42000.00, 'Hired as Midwife'),
(14, 31, '2022-01-10', NULL, 25000.00, 'Hired as Driver'),
(15, 33, '2021-11-05', NULL, 24000.00, 'Hired as Security Personnel'),

-- Previous positions showing career progression
(1, 26, '2017-03-01', '2019-06-30', 25000.00, 'Started as Administrative Aide'),
(2, 21, '2015-08-01', '2018-06-14', 32000.00, 'Started as CAD Operator'),
(5, 26, '2019-01-15', '2021-09-04', 25000.00, 'Started as Administrative Aide'),
(9, 27, '2018-05-01', '2020-09-09', 22000.00, 'Started as Clerk'),
(10, 27, '2017-10-01', '2019-11-30', 20000.00, 'Started as Clerk');

-- Insert sample data for document_management
INSERT INTO document_management (employee_id, document_type, document_name, file_path, expiry_date, document_status, notes) VALUES
-- Appointment papers and contracts
(1, 'Appointment', 'Appointment Order - Municipal Treasurer', '/documents/appointments/maria_santos_appointment.pdf', NULL, 'Active', 'Appointed by Mayor per Civil Service guidelines'),
(1, 'Contract', 'Employment Contract - Municipal Treasurer', '/documents/contracts/maria_santos_contract.pdf', '2025-07-01', 'Active', 'Department head contract'),
(1, 'Resume', 'Resume - Maria Santos', '/documents/resumes/maria_santos_resume.pdf', NULL, 'Active', 'CPA with municipal finance experience'),

(2, 'Appointment', 'Appointment Order - Municipal Engineer', '/documents/appointments/roberto_cruz_appointment.pdf', NULL, 'Active', 'Licensed Civil Engineer appointment'),
(2, 'Certificate', 'Professional Engineer License', '/documents/licenses/roberto_cruz_pe_license.pdf', '2025-12-31', 'Active', 'Updated PRC license'),
(2, 'Contract', 'Employment Contract - Municipal Engineer', '/documents/contracts/roberto_cruz_contract.pdf', '2024-06-15', 'Active', 'Engineering department head'),

(3, 'Contract', 'Employment Contract - Nurse', '/documents/contracts/jennifer_reyes_contract.pdf', '2025-01-20', 'Active', 'Municipal health office nurse'),
(3, 'Certificate', 'Nursing License', '/documents/licenses/jennifer_reyes_rn_license.pdf', '2025-08-31', 'Active', 'Updated PRC nursing license'),
(3, 'Certificate', 'Basic Life Support Training', '/documents/certificates/jennifer_reyes_bls_cert.pdf', '2024-12-31', 'Active', 'Required medical certification'),

(4, 'Contract', 'Employment Contract - CAD Operator', '/documents/contracts/antonio_garcia_contract.pdf', '2024-03-10', 'Active', 'Engineering support staff'),
(4, 'Certificate', 'AutoCAD Certification', '/documents/certificates/antonio_garcia_autocad_cert.pdf', '2025-06-30', 'Active', 'Professional CAD certification'),

(5, 'Contract', 'Employment Contract - Social Worker', '/documents/contracts/lisa_mendoza_contract.pdf', '2024-09-05', 'Active', 'MSWDO social worker'),
(5, 'Certificate', 'Social Work License', '/documents/licenses/lisa_mendoza_sw_license.pdf', '2025-10-31', 'Active', 'Updated PRC social work license'),

(6, 'Contract', 'Employment Contract - Accounting Staff', '/documents/contracts/michael_torres_contract.pdf', '2025-11-12', 'Active', 'Municipal accountant office staff'),
(6, 'Certificate', 'Bookkeeping Certification', '/documents/certificates/michael_torres_bookkeeping_cert.pdf', '2024-12-31', 'Active', 'Professional bookkeeping certification'),

(7, 'Contract', 'Employment Contract - Clerk', '/documents/contracts/carmen_delacruz_contract.pdf', '2025-02-28', 'Active', 'Civil registrar office clerk'),
(7, 'Training', 'Civil Registration Training', '/documents/training/carmen_delacruz_civil_reg_training.pdf', NULL, 'Active', 'Specialized civil registration procedures'),

(8, 'Contract', 'Employment Contract - Maintenance Worker', '/documents/contracts/ricardo_villanueva_contract.pdf', '2024-05-18', 'Active', 'General services maintenance'),
(8, 'Certificate', 'Electrical Safety Training', '/documents/certificates/ricardo_villanueva_electrical_safety.pdf', '2024-12-31', 'Active', 'Safety certification for maintenance work'),

(9, 'Contract', 'Employment Contract - Cashier', '/documents/contracts/sandra_pascual_contract.pdf', '2025-09-10', 'Active', 'Treasury office cashier'),
(9, 'Training', 'Financial Management Training', '/documents/training/sandra_pascual_finance_training.pdf', NULL, 'Active', 'Municipal financial procedures training'),

(10, 'Contract', 'Employment Contract - Collection Officer', '/documents/contracts/jose_ramos_contract.pdf', '2024-12-01', 'Active', 'Revenue collection specialist'),
(10, 'Training', 'Revenue Collection Procedures', '/documents/training/jose_ramos_collection_training.pdf', NULL, 'Active', 'Specialized revenue collection training'),

(11, 'Contract', 'Employment Contract - Administrative Aide', '/documents/contracts/ana_morales_contract.pdf', '2025-04-15', 'Active', 'HR office administrative support'),

(12, 'Contract', 'Employment Contract - Agricultural Technician', '/documents/contracts/pablo_fernandez_contract.pdf', '2024-08-20', 'Active', 'Agriculture office technical staff'),
(12, 'Certificate', 'Agricultural Extension Training', '/documents/certificates/pablo_fernandez_agri_ext_cert.pdf', '2025-07-31', 'Active', 'Agricultural extension certification'),

(13, 'Contract', 'Employment Contract - Midwife', '/documents/contracts/grace_lopez_contract.pdf', '2025-06-30', 'Active', 'Municipal health office midwife'),
(13, 'Certificate', 'Midwifery License', '/documents/licenses/grace_lopez_midwife_license.pdf', '2025-09-30', 'Active', 'Updated PRC midwifery license'),

(14, 'Contract', 'Employment Contract - Driver', '/documents/contracts/eduardo_hernandez_contract.pdf', '2025-01-10', 'Active', 'Municipal vehicle operator'),
(14, 'Certificate', 'Professional Driver License', '/documents/licenses/eduardo_hernandez_driver_license.pdf', '2025-12-31', 'Active', 'Professional driver''s license'),

(15, 'Contract', 'Employment Contract - Security Personnel', '/documents/contracts/rosario_gonzales_contract.pdf', '2024-11-05', 'Active', 'Municipal facility security'),
(15, 'Certificate', 'Security Guard License', '/documents/licenses/rosario_gonzales_security_license.pdf', '2025-08-31', 'Active', 'SOSIA security guard license');
