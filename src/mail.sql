CREATE TABLE postfix_alias (
    id          int     (11)    unsigned NOT NULL auto_increment,
    alias       varchar (128)   NOT NULL default '',
    destination varchar (200)   NOT NULL default '',
    authcode    varchar (10)    NOT NULL default '',
    ip          varchar (16),
    addedon     datetime        NOT NULL,
    active      int     (1)     NOT NULL default 0,
    PRIMARY KEY (id)
);
