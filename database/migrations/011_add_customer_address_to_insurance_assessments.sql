ALTER TABLE insurance_assessments
    ADD COLUMN customer_street VARCHAR(255) NOT NULL DEFAULT '' AFTER assessment_result,
    ADD COLUMN customer_zip VARCHAR(10) NOT NULL DEFAULT '' AFTER customer_street,
    ADD COLUMN customer_city VARCHAR(100) NOT NULL DEFAULT '' AFTER customer_zip;
