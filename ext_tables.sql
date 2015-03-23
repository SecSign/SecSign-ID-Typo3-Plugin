CREATE TABLE fe_users (
        secsignid  varchar(255) DEFAULT '' NOT NULL,
        UNIQUE (secsignid)
);

CREATE TABLE be_users (
        secsignid  varchar(255) DEFAULT '' NOT NULL,
        UNIQUE (secsignid)
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