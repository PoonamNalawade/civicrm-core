{* file to handle db changes in 4.5.alpha1 during upgrade *}

ALTER TABLE `civicrm_contact`
  ADD COLUMN `formal_title` varchar(64) COMMENT 'Formal (academic or similar) title in front of name. (Prof., Dr. etc.)' AFTER `suffix_id`;

ALTER TABLE `civicrm_contact`
  ADD COLUMN `communication_style_id` int(10) unsigned COMMENT 'Communication style (e.g. formal vs. familiar) to use with this contact. FK to communication styles in civicrm_option_value.' AFTER `formal_title`,
  ADD INDEX `index_communication_style_id` (`communication_style_id`);

INSERT INTO
  `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`)
VALUES
  ('communication_style', {localize}'{ts escape="sql"}Communication Style{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_communication_style := max(id) from civicrm_option_group where name = 'communication_style';

INSERT INTO
  `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Formal{/ts}'{/localize}  , 1, 'formal'  , NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Familiar{/ts}'{/localize}, 2, 'familiar', NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL);

-- Insert menu item at Administer > Communications, above the various Greeting Formats

SELECT @parent_id := `id` FROM `civicrm_navigation` WHERE `name` = 'Communications' AND `domain_id` = {$domainID};
SELECT @add_weight := MIN(`weight`) FROM `civicrm_navigation` WHERE `name` IN('Email Greeting Formats', 'Postal Greeting Formats', 'Addressee Formats') AND `parent_id` = @parent_id;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` >= @add_weight;

INSERT INTO `civicrm_navigation`
  ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
  ( {$domainID}, 'civicrm/admin/options/communication_style&group=communication_style&reset=1', '{ts escape="sql" skip="true"}Communication Style Options{/ts}', 'Communication Style Options', 'administer CiviCRM', '', @parent_id, '1', NULL, @add_weight );

-- CRM-9988 Change world region of Panama country to America South, Central, North and Caribbean
UPDATE `civicrm_country` SET `region_id` = 2 WHERE `id` = 1166;

SELECT @option_group_id_contact_edit_options := max(id) from civicrm_option_group where name = 'contact_edit_options';

INSERT INTO
  `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Prefix{/ts}'{/localize}      , 12, 'Prefix'      , NULL, 2, NULL, 12, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Formal Title{/ts}'{/localize}, 13, 'Formal Title', NULL, 2, NULL, 13, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}First Name{/ts}'{/localize}  , 14, 'First Name'  , NULL, 2, NULL, 14, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Middle Name{/ts}'{/localize} , 15, 'Middle Name' , NULL, 2, NULL, 15, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Last Name{/ts}'{/localize}   , 16, 'Last Name'   , NULL, 2, NULL, 16, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Suffix{/ts}'{/localize}      , 17, 'Suffix'      , NULL, 2, NULL, 17, NULL, 0, 0, 1, NULL, NULL);

-- CRM-13712 Include IS NOT EMPTY and IS EMPTY operators in operator column of civicrm_mapping_field table
ALTER TABLE `civicrm_mapping_field`
  MODIFY `operator` ENUM('=','!=','>','<','>=','<=','IN','NOT IN','LIKE','NOT LIKE', 'IS NOT EMPTY', 'IS EMPTY') DEFAULT NULL COMMENT 'SQL WHERE operator for search-builder mapping fields (search criteria).';

-- CRM-13857
ALTER TABLE civicrm_group
  ADD COLUMN `modified_id` INT(10) unsigned DEFAULT NULL COMMENT 'FK to contact table, modifier of the group.',
  ADD CONSTRAINT `FK_civicrm_group_modified_id` FOREIGN KEY (`modified_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL;

-- CRM-13913
ALTER TABLE civicrm_word_replacement
  ALTER COLUMN `is_active` SET DEFAULT 1;

--CRM-13833 Implement Soft Credit Type for Contribution
INSERT INTO civicrm_option_group
      (name, {localize field='title'}title{/localize}, is_reserved, is_active) VALUES ('soft_credit_type', {localize}'{ts escape="sql"}Soft Credit Types{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_soft_credit_type := max(id) from civicrm_option_group where name = 'soft_credit_type';

INSERT INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, `is_default`, `is_active`, `is_reserved`) 
 VALUES
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}In Honor of{/ts}'{/localize}, 1, 'in_honor_of', 1, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}In Memory of{/ts}'{/localize}, 2, 'in_memory_of', 2, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Solicited{/ts}'{/localize}, 3, 'solicited', 3, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Household{/ts}'{/localize}, 4, 'household', 4, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Workplace Giving{/ts}'{/localize}, 5, 'workplace', 5, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Foundation Affiliate{/ts}'{/localize}, 6, 'foundation_affiliate', 6, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}3rd-party Service{/ts}'{/localize}, 7, '3rd-party_service', 7, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Donor-advised Fund{/ts}'{/localize}, 8, 'donor-advised_fund', 8, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Matched Gift{/ts}'{/localize}, 9, 'matched_gift', 9, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}PCP{/ts}'{/localize}, 10, 'pcp', 10, 0, 1, 1);

ALTER TABLE `civicrm_contribution_soft`
  ADD COLUMN `soft_credit_type_id`  int(10) unsigned COMMENT 'Soft Credit Type ID.Implicit FK to civicrm_option_value where option_group = soft_credit_type.';

SELECT @sct_pcp_id := value from civicrm_option_value where name = 'pcp' and option_group_id = @option_group_id_soft_credit_type;

UPDATE `civicrm_contribution_soft`
SET soft_credit_type_id = @sct_pcp_id
WHERE pcp_id IS NOT NULL;

--CRM-13734 make basic Case Activity Types reserved
SELECT @option_group_id_activity_type := id from civicrm_option_group where name = 'activity_type';
SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';

UPDATE `civicrm_option_value`
SET is_reserved = 1
WHERE is_reserved = 0 AND option_group_id = @option_group_id_activity_type AND component_id = @caseCompId;