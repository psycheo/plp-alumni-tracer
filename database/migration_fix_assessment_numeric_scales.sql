-- Fix numeric precision for 0-100 scales in alumni_assessments.
-- Previous DECIMAL(3,2) capped values at 9.99 and caused OJT/skills truncation.
ALTER TABLE `alumni_assessments`
  MODIFY `ojt_grade` DECIMAL(5,2) NOT NULL,
  MODIFY `soft_skills_avg` DECIMAL(5,2) NOT NULL,
  MODIFY `hard_skills_avg` DECIMAL(5,2) NOT NULL;

