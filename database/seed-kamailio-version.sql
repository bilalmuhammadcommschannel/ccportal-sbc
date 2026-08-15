-- Kamailio table_version rows. usrloc (location) and auth_db (subscriber) call
-- db_check_table_version() at startup; an empty `version` table returns 0 and
-- Kamailio aborts. These are the exact compiled-in versions for Kamailio 6.0.
-- Loaded UNCONDITIONALLY (upsert) so a re-run also repairs a box botched by an
-- earlier install where the schema load was skipped.
INSERT INTO version (table_name, table_version) VALUES
 ('acc',5),('acc_cdrs',2),('active_watchers',12),('address',6),('aliases',8),
 ('carrierfailureroute',2),('carrierroute',3),('carrier_name',1),('cpl',1),
 ('dbaliases',1),('dialog',7),('dialog_vars',1),('dialplan',2),('dispatcher',4),
 ('domain',2),('domainpolicy',2),('domain_attrs',1),('domain_name',1),
 ('dr_gateways',3),('dr_groups',2),('dr_gw_lists',1),('dr_rules',3),
 ('globalblacklist',1),('grp',2),('htable',2),('imc_members',1),('imc_rooms',1),
 ('lcr_gw',3),('lcr_rule',2),('lcr_rule_target',1),('location',9),
 ('location_attrs',1),('missed_calls',4),('mohqcalls',1),('mohqueues',1),
 ('mtree',1),('mtrees',2),('pdt',1),('pl_pipes',1),('presentity',4),('pua',7),
 ('purplemap',1),('re_grp',1),('rls_presentity',1),('rls_watchers',3),
 ('rtpproxy',1),('sca_subscriptions',1),('silo',8),('sip_trace',4),
 ('speed_dial',2),('subscriber',6),('switch_user_sip',6),('topos_d',1),
 ('topos_t',1),('trusted',6),('uacreg',2),('uid_credentials',7),('uid_domain',2),
 ('uid_domain_attrs',1),('uid_global_attrs',1),('uid_uri',3),('uid_uri_attrs',2),
 ('uid_user_attrs',3),('uri',1),('userblacklist',1),('usr_preferences',2),
 ('version',1),('watchers',3),('xcap',4)
ON DUPLICATE KEY UPDATE table_version=VALUES(table_version);
