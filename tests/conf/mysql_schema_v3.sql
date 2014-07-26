drop table if exists ttrss_version;
drop table if exists ttrss_labels;
drop table if exists ttrss_filters;
drop table if exists ttrss_filter_types;
drop table if exists ttrss_filter_actions;
drop table if exists ttrss_user_prefs;
drop table if exists ttrss_prefs;
drop table if exists ttrss_prefs_types;
drop table if exists ttrss_prefs_sections;
drop table if exists ttrss_tags;
drop table if exists ttrss_entry_comments;
drop table if exists ttrss_user_entries;
drop table if exists ttrss_entries;
drop table if exists ttrss_feeds;
drop table if exists ttrss_feed_categories;
drop table if exists ttrss_users;
drop table if exists ttrss_themes;

begin;

create table ttrss_themes(id integer not null primary key auto_increment,
	theme_name varchar(200) not null,
	theme_path varchar(200) not null) ENGINE=InnoDB;

create table ttrss_users (id integer primary key not null auto_increment,
	login varchar(120) not null unique,
	pwd_hash varchar(250) not null,
	last_login datetime default '1970-01-01 00:00:00',
	access_level integer not null default 0,
	theme_id integer default null,
	email varchar(250) not null default '',
	index (theme_id),
	foreign key (theme_id) references ttrss_themes(id)) ENGINE=InnoDB;

insert into ttrss_users (login,pwd_hash,access_level) values ('admin', 'password', 10);

create table ttrss_feed_categories(id integer not null primary key auto_increment,
	owner_uid integer not null,
	title varchar(200) not null,
	collapsed bool not null default false,
	index(owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE) ENGINE=InnoDB;

create table ttrss_feeds (id integer not null auto_increment primary key,
	owner_uid integer not null,
	title varchar(200) not null,
	cat_id integer default null,
	feed_url varchar(250) not null,
	icon_url varchar(250) not null default '',
	update_interval integer not null default 0,
	purge_interval integer not null default 0,
	last_updated datetime default '1970-01-01 00:00:00',
	last_error text not null default '',
	site_url varchar(250) not null default '',
	auth_login varchar(250) not null default '',
	auth_pass varchar(250) not null default '',
	index(owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE,
	index(cat_id),
	foreign key (cat_id) references ttrss_feed_categories(id)) ENGINE=InnoDB;

insert into ttrss_feeds (owner_uid,title,feed_url) values (1,'Footnotes', 'http://gnomedesktop.org/node/feed');
insert into ttrss_feeds (owner_uid,title,feed_url) values (1,'Latest Linux Kernel Versions','http://kernel.org/kdist/rss.xml');
insert into ttrss_feeds (owner_uid,title,feed_url) values (1,'RPGDot Newsfeed',
   'http://www.rpgdot.com/team/rss/rss0.xml');
insert into ttrss_feeds (owner_uid,title,feed_url) values (1,'Digg.com News',
   'http://digg.com/rss/index.xml');
insert into ttrss_feeds (owner_uid,title,feed_url) values (1,'Technocrat.net',
   'http://syndication.technocrat.net/rss');

create table ttrss_entries (id integer not null primary key auto_increment,
	title text not null,
	guid varchar(255) not null unique,
	link text not null,
	updated datetime not null,
	content text not null,
	content_hash varchar(250) not null,
	no_orig_date bool not null default 0,
	date_entered datetime not null,
	num_comments integer not null default 0,
	comments varchar(250) not null default '') ENGINE=InnoDB;

create table ttrss_user_entries (
	int_id integer not null primary key auto_increment,
	ref_id integer not null,
	feed_id int not null,
	owner_uid integer not null,
	marked bool not null default 0,
	last_read datetime,
	unread bool not null default 1,
	index (ref_id),
	foreign key (ref_id) references ttrss_entries(id) ON DELETE CASCADE,
	index (feed_id),
	foreign key (feed_id) references ttrss_feeds(id) ON DELETE CASCADE,
	index (owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE) ENGINE=InnoDB;

create table ttrss_entry_comments (id integer not null primary key,
	ref_id integer not null,
	owner_uid integer not null,
	private bool not null default 0,
	date_entered datetime not null,
	index (ref_id),
	foreign key (ref_id) references ttrss_entries(id) ON DELETE CASCADE,
	index (owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE) ENGINE=InnoDB;

create table ttrss_filter_types (id integer primary key,
	name varchar(120) unique not null,
	description varchar(250) not null unique) ENGINE=InnoDB;

insert into ttrss_filter_types (id,name,description) values (1, 'title', 'Title');
insert into ttrss_filter_types (id,name,description) values (2, 'content', 'Content');
insert into ttrss_filter_types (id,name,description) values (3, 'both',
	'Title or Content');
insert into ttrss_filter_types (id,name,description) values (4, 'link',
	'Link');

create table ttrss_filter_actions (id integer not null primary key,
	name varchar(120) unique not null,
	description varchar(250) not null unique) ENGINE=InnoDB;

insert into ttrss_filter_actions (id,name,description) values (1, 'filter',
	'Filter article');

insert into ttrss_filter_actions (id,name,description) values (2, 'catchup',
	'Mark as read');

create table ttrss_filters (id integer not null primary key auto_increment,
	owner_uid integer not null,
	feed_id integer default null,
	filter_type integer not null,
	reg_exp varchar(250) not null,
	description varchar(250) not null default '',
	action_id integer not null default 1,
	index (filter_type),
	foreign key (filter_type) references ttrss_filter_types(id) ON DELETE CASCADE,
	index (owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE,
	index (feed_id),
	foreign key (feed_id) references ttrss_feeds(id) ON DELETE CASCADE,
	index (action_id),
	foreign key (action_id) references ttrss_filter_actions(id) ON DELETE CASCADE) ENGINE=InnoDB;

create table ttrss_labels (id integer not null primary key auto_increment,
	owner_uid integer not null,
	sql_exp varchar(250) not null,
	description varchar(250) not null,
	index (owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE) ENGINE=InnoDB;

insert into ttrss_labels (owner_uid,sql_exp,description) values (1,'unread = true',
	'Unread articles');

insert into ttrss_labels (owner_uid,sql_exp,description) values (1,
	'last_read is null and unread = false', 'Updated articles');

create table ttrss_tags (id integer primary key auto_increment,
	owner_uid integer not null,
	tag_name varchar(250) not null,
	post_int_id integer not null,
	index (post_int_id),
	foreign key (post_int_id) references ttrss_user_entries(int_id) ON DELETE CASCADE,
	index (owner_uid),
	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE) ENGINE=InnoDB;

create table ttrss_version (schema_version int not null) ENGINE=InnoDB;

insert into ttrss_version values (3);

create table ttrss_prefs_types (id integer not null primary key,
	type_name varchar(100) not null) ENGINE=InnoDB;

insert into ttrss_prefs_types (id, type_name) values (1, 'bool');
insert into ttrss_prefs_types (id, type_name) values (2, 'string');
insert into ttrss_prefs_types (id, type_name) values (3, 'integer');

create table ttrss_prefs_sections (id integer not null primary key,
	section_name varchar(100) not null) ENGINE=InnoDB;

insert into ttrss_prefs_sections (id, section_name) values (1, 'General');
insert into ttrss_prefs_sections (id, section_name) values (2, 'Interface');
insert into ttrss_prefs_sections (id, section_name) values (3, 'Advanced');

create table ttrss_prefs (pref_name varchar(250) not null primary key,
	type_id integer not null,
	section_id integer not null default 1,
	short_desc text not null,
	help_text text not null default '',
	def_value text not null,
	index(type_id),
	foreign key (type_id) references ttrss_prefs_types(id),
	index(section_id),
	foreign key (section_id) references ttrss_prefs_sections(id)) ENGINE=InnoDB;

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('ENABLE_FEED_ICONS', 1, 'true', 'Enable icons in feedlist',2);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('PURGE_OLD_DAYS', 3, '60', 'Purge old posts after this number of days (0 - disables)',1);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('UPDATE_POST_ON_CHECKSUM_CHANGE', 1, 'true', 'Update post on checksum change',1);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('ENABLE_PREFS_CATCHUP_UNCATCHUP', 1, 'false', 'Enable catchup/uncatchup buttons in feed editor',2);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id,help_text) values('ENABLE_LABELS', 1, 'false', 'Enable labels',3,
   'Experimental support for virtual feeds based on user crafted SQL queries. This feature is highly experimental and at this point not user friendly. Use with caution.');

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('DEFAULT_UPDATE_INTERVAL', 3, '30', 'Default interval between feed updates (in minutes)',1);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('DISPLAY_HEADER', 1, 'true', 'Display header',2);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('DISPLAY_FOOTER', 1, 'true', 'Display footer',2);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('USE_COMPACT_STYLESHEET', 1, 'false', 'Use compact stylesheet by default',2);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id,help_text) values('DEFAULT_ARTICLE_LIMIT', 3, '0', 'Default article limit',2,
   'Default limit for articles to display, any custom number you like (0 - disables).');

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id,help_text) values('DISPLAY_FEEDLIST_ACTIONS', 1, 'false', 'Display feedlist actions',2,
   'Display separate dropbox for feedlist actions, if disabled these actions are available in global actions menu.');

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id,help_text) values('ALLOW_DUPLICATE_POSTS', 1, 'true', 'Allow duplicate posts',1,
	'This option is useful when you are reading several planet-type aggregators with partially colliding userbase.
	When disabled, it forces same posts from different feeds to appear only once.');

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id,help_text) values('USER_STYLESHEET_URL', 2, '', 'User stylesheet URL',2,
	'Link to user stylesheet to override default style, disabled if empty.');

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('ENABLE_FEED_CATS', 1, 'false', 'Enable feed categories',2);

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('SHOW_CONTENT_PREVIEW', 1, 'true', 'Show content preview in headlines list',2);

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('SHORT_DATE_FORMAT', 2, 'M d, G:i', 'Short date format',3);
insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('LONG_DATE_FORMAT', 2, 'D, M d Y - G:i', 'Long date format',3);

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('HEADLINES_SMART_DATE', 1, 'true', 'Use more accessible date/time format for headlines',3);

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id,help_text) values('COMBINED_DISPLAY_MODE', 1, 'false', 'Combined feed display',2,
	'Display expanded list of feed articles, instead of separate displays for headlines and article content');

insert into ttrss_prefs (pref_name,type_id,def_value,short_desc,section_id) values('ENABLE_SEARCH_TOOLBAR', 1, 'false', 'Enable search toolbar',2);

create table ttrss_user_prefs (
   owner_uid integer not null,
   pref_name varchar(250),
   value text not null,
	index (owner_uid),
 	foreign key (owner_uid) references ttrss_users(id) ON DELETE CASCADE,
	index (pref_name),
	foreign key (pref_name) references ttrss_prefs(pref_name) ON DELETE CASCADE) ENGINE=InnoDB;

commit;
