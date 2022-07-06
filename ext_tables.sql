CREATE TABLE be_users (
    chooseToActivate varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_users (
    needsAccessToken BINARY DEFAULT 0 NOT NULL
);

CREATE TABLE be_users (
    tokenID varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_users (
    secsignid varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_users (
    secsignid_temp varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_users (
    activeMethods varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_users (
    lastMethod varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tx_secsign (
        service_name  varchar(255) DEFAULT '' NOT NULL,
        pre_text  varchar(255) DEFAULT '' NOT NULL,
        post_text  varchar(255) DEFAULT '' NOT NULL,
        login_redirect  varchar(255) DEFAULT '' NOT NULL,
        logout_redirect  varchar(255) DEFAULT '' NOT NULL,
        show_greeting  varchar(255) DEFAULT '' NOT NULL,
        show_username  varchar(255) DEFAULT '' NOT NULL,
        be_login_method varchar(255) DEFAULT '' NOT NULL,
        be_service_name  varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE secsign_hashes_be (
        secsignid  varchar(255) DEFAULT '' NOT NULL,    
        authedHash  varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_groups (
    needs_twofa BINARY DEFAULT 0 NOT NULL,
    allowed_methods varchar(255) DEFAULT 'secsignid' NOT NULL
);






CREATE TABLE fe_users (
    chooseToActivate varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    needsAccessToken BINARY DEFAULT 0 NOT NULL
);

CREATE TABLE fe_users (
    tokenID varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    secsignid varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    secsignid_temp varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    activeMethods varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    lastMethod varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_groups (
    needs_twofa BINARY DEFAULT 0 NOT NULL,
    allowed_methods varchar(255) DEFAULT 'secsignid' NOT NULL   
);


CREATE TABLE secsign_hashes (
        secsignid  varchar(255) DEFAULT '' NOT NULL,    
        authedHash  varchar(255) DEFAULT '' NOT NULL
);
