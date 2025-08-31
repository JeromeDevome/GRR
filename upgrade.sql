ALTER TABLE grr438.grr_j_user_site DROP PRIMARY KEY;
ALTER TABLE grr438.grr_j_user_site ADD CONSTRAINT grr_j_user_site_pk PRIMARY KEY (login,id_site,idgroupes);