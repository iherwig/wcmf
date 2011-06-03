# this installs the base tables required by the framework.
# Normally you don't need this file: use install.php instead.
# 
# Tabellenstruktur für Tabelle `adodbseq`
#

DROP TABLE IF EXISTS `adodbseq`;
CREATE TABLE `adodbseq` (
  `id` int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Daten für Tabelle `adodbseq`
#

INSERT INTO `adodbseq` (`id`) VALUES (100);

#
# Tabellenstruktur für Tabelle `nm_user_role`
#

DROP TABLE IF EXISTS `nm_user_role`;
CREATE TABLE `nm_user_role` (
  `fk_user_id` int(11) NOT NULL default '0',
  `fk_role_id` int(11) NOT NULL default '0',
  KEY `fk_user_id` (`fk_user_id`,`fk_role_id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `nm_user_role`
#

INSERT INTO `nm_user_role` (`fk_user_id`, `fk_role_id`) VALUES (0, 0);

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `role`
#

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `id` int(11) NOT NULL default '0',
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;

#
# Daten für Tabelle `role`
#

INSERT INTO `role` (`id`, `name`) VALUES (0, 'administrators');

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `user`
#

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL default '0',
  `name` varchar(50) default NULL,
  `firstname` varchar(50) default NULL,
  `login` varchar(50) default NULL,
  `password` varchar(50) default NULL,
  `config` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `user`
#

INSERT INTO `user` (`id`, `name`, `firstname`, `login`, `password`, `config`) VALUES (0, 'Administrator', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'include/admin.ini');
INSERT INTO `user` (`id`, `name`, `firstname`, `login`, `password`, `config`) VALUES (1, 'User', '', 'user', 'ee11cbb19052e40b07aac0ca060c23ee', '');
 
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `locktable`
#

DROP TABLE IF EXISTS `locktable`;
CREATE TABLE `locktable` (
  `id` int(11) NOT NULL default '0',
  `oid` varchar(255) NOT NULL default '',
  `sid` varchar(255) NOT NULL default '',
  `fk_user_id` int(11) NOT NULL default '0',
  `since` datetime NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;
