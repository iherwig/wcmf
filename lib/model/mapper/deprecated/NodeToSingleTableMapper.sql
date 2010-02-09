#
# Tabellen für NodeToSingleTableMapper
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `attrib_def`
#

DROP TABLE IF EXISTS attrib_def;
CREATE TABLE attrib_def (
  id int(11) NOT NULL auto_increment,
  attrib_name varchar(50) NOT NULL default '',
  fk_data_types_id int(11) NOT NULL default '0',
  fk_elements_id int(11) NOT NULL default '0',
  optional tinyint(4) NOT NULL default '0',
  restrictions text,
  defaultval varchar(255) default NULL,
  visible tinyint(4) NOT NULL default '1',
  editable tinyint(4) NOT NULL default '1',
  alt varchar(255) default NULL,
  hint varchar(255) default NULL,
  input_type varchar(255) default NULL,
  sort_key float default NULL,
  PRIMARY KEY  (id),
  KEY fk_data_types_id (fk_data_types_id),
  KEY fk_elements_id (fk_elements_id),
  KEY sort_key (sort_key)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `attribs`
#

DROP TABLE IF EXISTS attribs;
CREATE TABLE attribs (
  id int(11) NOT NULL auto_increment,
  fk_nodes_id int(11) NOT NULL default '0',
  fk_attrib_def_id int(11) NOT NULL default '0',
  data_txt varchar(255) default NULL,
  data_blob longtext,
  data_date datetime default NULL,
  data_float float default NULL,
  data_int int(11) default NULL,
  data_boolean tinyint(4) default NULL,
  sort_key float default NULL,
  PRIMARY KEY  (id),
  KEY fk_a_attrib_def_id (fk_attrib_def_id),
  KEY fk_nodes_id (fk_nodes_id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `data_types`
#

DROP TABLE IF EXISTS data_types;
CREATE TABLE data_types (
  id int(11) NOT NULL auto_increment,
  data_type char(50) NOT NULL default '',
  sort_key float default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `element_relations`
#

DROP TABLE IF EXISTS element_relations;
CREATE TABLE element_relations (
  fk_elements_id int(11) NOT NULL default '0',
  fk_elements_child_id int(11) NOT NULL default '0',
  repetitive tinyint(4) default NULL,
  optional tinyint(4) default NULL,
  grouproot int(11) NOT NULL default '0',
  sort_key float default NULL,
  KEY fk_elements_child_id (fk_elements_child_id),
  KEY fk_elements_id (fk_elements_id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `elements`
#

DROP TABLE IF EXISTS elements;
CREATE TABLE elements (
  id int(11) NOT NULL auto_increment,
  element_name varchar(50) NOT NULL default '',
  fk_data_types_id int(11) NOT NULL default '0',
  restrictions varchar(255) default NULL,
  defaultval varchar(255) default NULL,
  visible tinyint(4) NOT NULL default '1',
  editable tinyint(4) NOT NULL default '1',
  alt varchar(255) default NULL,
  hint varchar(255) default NULL,
  display_value varchar(255) default NULL,
  input_type varchar(255) default NULL,
  sort_key float default NULL,
  PRIMARY KEY  (id),
  KEY fk_data_types_id (fk_data_types_id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `nodes`
#

DROP TABLE IF EXISTS nodes;
CREATE TABLE nodes (
  id int(11) NOT NULL auto_increment,
  fk_nodes_id int(11) NOT NULL default '0',
  fk_n_elements_id int(11) NOT NULL default '0',
  data_txt varchar(255) default NULL,
  data_blob longtext,
  data_date datetime default NULL,
  data_float float default NULL,
  data_int int(11) default NULL,
  data_boolean tinyint(4) default NULL,
  sort_key float default NULL,
  PRIMARY KEY  (id),
  KEY fk_n_elements_id (fk_n_elements_id),
  KEY fk_nodes_id (fk_nodes_id)
) TYPE=MyISAM;
# --------------------------------------------------------

    