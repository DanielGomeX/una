-- FORMS
DELETE FROM `sys_form_inputs` WHERE `object`='bx_organization' AND `name` IN ('profile_email', 'profile_status');
INSERT INTO `sys_form_inputs`(`object`, `module`, `name`, `value`, `values`, `checked`, `type`, `caption_system`, `caption`, `info`, `required`, `collapsed`, `html`, `attrs`, `attrs_tr`, `attrs_wrapper`, `checker_func`, `checker_params`, `checker_error`, `db_pass`, `db_params`, `editable`, `deletable`) VALUES 
('bx_organization', 'bx_organizations', 'profile_email', '', '', 0, 'text', '_bx_orgs_form_profile_input_sys_profile_email', '_bx_orgs_form_profile_input_profile_email', '', 0, 0, 0, '', '', '', '', '', '', 'Xss', '', 1, 0),
('bx_organization', 'bx_organizations', 'profile_status', '', '', 0, 'custom', '_bx_orgs_form_profile_input_sys_profile_status', '_bx_orgs_form_profile_input_profile_status', '', 0, 0, 0, '', '', '', '', '', '', 'Xss', '', 1, 0);

DELETE FROM `sys_form_display_inputs` WHERE `display_name` IN ('bx_organization_view', 'bx_organization_view_full');
INSERT INTO `sys_form_display_inputs`(`display_name`, `input_name`, `visible_for_levels`, `active`, `order`) VALUES 
('bx_organization_view', 'org_name', 2147483647, 1, 1),
('bx_organization_view', 'org_cat', 2147483647, 1, 2),
('bx_organization_view', 'profile_email', 192, 1, 3),
('bx_organization_view', 'profile_status', 192, 1, 4),

('bx_organization_view_full', 'org_name', 2147483647, 1, 1),
('bx_organization_view_full', 'org_cat', 2147483647, 1, 2),
('bx_organization_view_full', 'org_desc', 2147483647, 1, 3),
('bx_organization_view_full', 'profile_email', 192, 1, 4),
('bx_organization_view_full', 'profile_status', 192, 1, 5);


-- CONTENT INFO
DELETE FROM `sys_objects_content_info` WHERE `name` IN ('bx_organizations', 'bx_organizations_cmts');
INSERT INTO `sys_objects_content_info` (`name`, `title`, `alert_unit`, `alert_action_add`, `alert_action_update`, `alert_action_delete`, `class_name`, `class_file`) VALUES
('bx_organizations', '_bx_orgs', 'bx_organizations', 'added', 'edited', 'deleted', '', ''),
('bx_organizations_cmts', '_bx_orgs_cmts', 'bx_organizations', 'commentPost', 'commentUpdated', 'commentRemoved', 'BxDolContentInfoCmts', '');

DELETE FROM `sys_content_info_grids` WHERE `object`='bx_organizations';
INSERT INTO `sys_content_info_grids` (`object`, `grid_object`, `grid_field_id`, `condition`, `selection`) VALUES
('bx_organizations', 'bx_organizations_administration', 'td`.`id', '', ''),
('bx_organizations', 'bx_organizations_common', 'td`.`id', '', '');


-- SEARCH EXTENDED
DELETE FROM `sys_objects_search_extended` WHERE `module`='bx_organizations';
INSERT INTO `sys_objects_search_extended` (`object`, `object_content_info`, `module`, `title`, `active`, `class_name`, `class_file`) VALUES
('bx_organizations', 'bx_organizations', 'bx_organizations', '_bx_orgs_search_extended', 1, '', ''),
('bx_organizations_cmts', 'bx_organizations_cmts', 'bx_organizations', '_bx_orgs_search_extended_cmts', 1, 'BxTemplSearchExtendedCmts', '');


-- REASSIGN PROFILES
UPDATE `sys_accounts` AS `a` INNER JOIN `sys_profiles` AS `p` ON `a`.`id`=`p`.`account_id` AND `p`.`type`<>'system' AND `a`.`profile_id`='0' SET `a`.`profile_id`=`p`.`id`;
UPDATE `sys_accounts` AS `a` INNER JOIN `sys_profiles` AS `p` ON `a`.`id`=`p`.`account_id` AND `p`.`type`='system' AND `a`.`profile_id`='0' SET `a`.`profile_id`=`p`.`id`;
