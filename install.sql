# 创建Card数据表

create table if not exists card
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;


alter table card
    add name varchar(200) null;

alter table card
    add name_en varchar(200) null;

alter table card
    add status int default 0 null;

alter table card
    add version int default 0 null;

alter table card
    add tips varchar(255) null ;

alter table card
    add create_time datetime null ;

alter table card
    add src text null ;

alter table card
    add url varchar(255) null ;

alter table card
    add `window` varchar(255) null ;

alter table card
    add update_time datetime null;

alter table card
    add install_num int default 0 null;

alter table card
    add setting varchar(200) null ;

alter table card
    add dict_option longtext null ;

create unique index card_pk on card (name_en);

create index card_name_en_index
    on card (name_en);

#创建config数据表

create table if not exists config
(
    user_id int null
) ;

create index config_user_id_index
    on config (user_id);

alter table config
    add config longtext null;

# 创建file数据表

create table if not exists file
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;

alter table file
    add path varchar(255) null;

alter table file
    add user_id int null;

alter table file
    add create_time datetime null;

alter table file
    add size double default 0 null ;

alter table file
    add mime_type varchar(100) null ;

alter table file
    add hash varchar(100) null ;


#创建history数据表

create table if not exists history
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    constraint history_id_uindex
        unique (id)
) ;

alter table history
    add user_id int null;

alter table history
    add link longtext null;

alter table history
    add create_time datetime null ;


#创建link数据表

create table if not exists link
(
    user_id int null
) ;

create index link_user_id_index
    on link (user_id);


alter table link
    add update_time datetime null ;

alter table link
    add link longtext null;


#创建link_folder数据表

create table if not exists link_folder
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;

alter table link_folder
    add name varchar(50) null ;

alter table link_folder
    add sort int default 0 null;

alter table link_folder
    add group_ids varchar(200) default '0' null ;

#创建link_store数据表

create table if not exists linkstore
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    constraint linkStore_id_uindex
        unique (id)
) ;

alter table linkstore
    add name varchar(255) null;

alter table linkstore
    add src varchar(255) null;

alter table linkstore
    add url text null;

alter table linkstore
    add type varchar(20) default 'icon' null;

alter table linkstore
    add size varchar(20) default '1x1' null;

alter table linkstore
    add create_time datetime null;

alter table linkstore
    add hot bigint default 0 null;

alter table linkstore
    add area varchar(20) default '' null ;

alter table linkstore
    add tips varchar(255) null ;

alter table linkstore
    add domain varchar(255) null;

alter table linkstore
    add app int default 0 null ;

alter table linkstore
    add install_num int default 0 null ;

alter table linkstore
    add bgColor varchar(30) null ;

alter table linkstore
    add vip int default 0 null ;

alter table linkstore
    add custom text null ;

alter table linkstore
    add user_id int null ;

alter table linkstore
    add status int default 1 null ;

alter table linkstore
    add group_ids varchar(200) default '0' null ;


#创建note数据表

create table if not exists note
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    constraint note_id_uindex
        unique (id)
) ;

alter table note
    add user_id bigint null;

alter table note
    add title varchar(50) null;

alter table note
    add text text null;

alter table note
    add create_time datetime null;

alter table note
    add update_time datetime null;

alter table note
    add weight int default 0 null;


alter table note
    add sort int default 0 null;

create index note_user_id_index
    on note (user_id);


#创建search_engine数据表

create table if not exists search_engine
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;

alter table search_engine
    add name varchar(50) null ;

alter table search_engine
    add icon varchar(255) null ;

alter table search_engine
    add url varchar(255) null ;

alter table search_engine
    add sort int default 0 null ;

alter table search_engine
    add create_time datetime null ;

alter table search_engine
    add status int default 0 null ;

alter table search_engine
    add tips varchar(250) null ;


#创建setting表

create table if not exists setting
(
    `keys` varchar(200) not null
        primary key
) ;

alter table setting
    add value text null;

#创建tabbar数据表

create table if not exists tabbar
(
    user_id int null
) ;

alter table tabbar
    add tabs longtext null;

alter table tabbar
    add update_time datetime null;

#创建token表

create table if not exists token
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    constraint token_id_uindex
        unique (id)
) ;

alter table token
    add user_id int null;

alter table token
    add token tinytext null;

alter table token
    add create_time int null;

alter table token
    add ip tinytext null;

alter table token
    add user_agent tinytext null;

alter table token
    add access_token varchar(200) null ;


#创建user表

create table if not exists user
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    constraint user_id_uindex
        unique (id)
) ;

alter table user
    add avatar varchar(255) null ;

alter table user
    add mail varchar(50) null;

alter table user
    add password tinytext null;

alter table user
    add create_time datetime null;

alter table user
    add login_ip varchar(100) null ;

alter table user
    add register_ip varchar(100) null ;

alter table user
    add manager int default 0 null;

alter table user
    add login_fail_count int default 0 null;

alter table user
    add login_time datetime null ;

alter table user
    add qq_open_id varchar(200) null ;

alter table user
    add nickname varchar(200) null ;

alter table user
    add status int default 0 null ;

alter table user
    add active date null ;

alter table user
    add group_id bigint default 0 null;

create unique index user_pk on user (mail);

create unique index user_pk_2 on user (qq_open_id);



#创建user_search_engine表

create table if not exists user_search_engine
(
    user_id int not null
        primary key,
    constraint user_search_engine_pk
        unique (user_id)
) ;

alter table user_search_engine
    add list longtext null;


#创建wallpaper表

create table if not exists wallpaper
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;

alter table wallpaper
    add type int null ;

alter table wallpaper
    add folder int null ;

alter table wallpaper
    add mime int default 0 null ;

alter table wallpaper
    add url text null ;

alter table wallpaper
    add cover text null ;

alter table wallpaper
    add create_time datetime null;

alter table wallpaper
    add name varchar(200) null ;

alter table wallpaper
    add sort int default 999 null;


create table user_group
(
    id INTEGER PRIMARY KEY AUTOINCREMENT 
)
    ;

alter table user_group
    add name varchar(50) not null ;

alter table user_group
    add create_time datetime null ;

alter table user_group
    add sort int default 0 null ;


##创建结束


##卡片组件安装部分

# 创建待办内容数据表

create table if not exists plugins_todo
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;


alter table plugins_todo
    add status int default 0 null ;

alter table plugins_todo
    add user_id int null;

alter table plugins_todo
    add create_time datetime null;

alter table plugins_todo
    add expire_time datetime null;

alter table plugins_todo
    add todo text(1000) null;

alter table plugins_todo
    add weight int null ;

alter table plugins_todo
    add folder varchar(20) null ;

create index plugins_todo_user_id_index
    on plugins_todo (user_id);

# 创建待办文件夹数据表

create table if not exists plugins_todo_folder
(
    id INTEGER PRIMARY KEY AUTOINCREMENT
) ;

alter table plugins_todo_folder
    add column user_id int null ;

alter table plugins_todo_folder
    add column name varchar(30) null;

alter table plugins_todo_folder
    add column create_time datetime null;

create index plugins_todo_folder_user_id_index
    on plugins_todo_folder (user_id);


INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('今天吃什么', 'food', 3, '吃什么是个很麻烦的事情', '/plugins/food/static/ico.png', '/plugins/food/card', '/plugins/food/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('天气', 'weather', 13, '获取您所在地的实时天气！', '/plugins/weather/static/ico.png', '/plugins/weather/card', '/plugins/weather/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('电子木鱼', 'muyu', 5, '木鱼一敲 烦恼丢掉', '/plugins/muyu/static/ico.png', '/plugins/muyu/card', '/plugins/muyu/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('热搜', 'topSearch', 15, '聚合百度，哔站，微博，知乎，头条等热搜！', '/plugins/topSearch/static/ico.png', '/plugins/topSearch/card', '/plugins/topSearch/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('记事本', 'noteApp', 15, '快捷记录您的灵感', '/plugins/noteApp/static/ico.png', '/plugins/noteApp/card', '/noteApp')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('每日诗词', 'poetry', 8, '精选每日诗词！', '/plugins/poetry/static/ico.png', '/plugins/poetry/card', '/plugins/poetry/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('日历', 'calendar', 1, '日历', '/plugins/calendar/static/ico.png', '/plugins/calendar/card', '/plugins/calendar/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('待办事项', 'todo', 8, '快捷添加待办事项', '/plugins/todo/static/ico.png', '/plugins/todo/card', '/plugins/todo/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('倒计时', 'countdown', 8, '个性化自定义事件的倒计时组件', '/plugins/countdown/static/ico.png', '/plugins/countdown/card', '/plugins/countdown/window')
;

INSERT OR REPLACE INTO card (name, name_en, version, tips, src, url, `window`)
VALUES ('纪念日', 'commemorate', 8, '个性化自定义事件的纪念日组件', '/plugins/commemorate/static/ico.png', '/plugins/commemorate/card', '/plugins/commemorate/window')
;