CREATE TABLE `grr_j_user_site` (
  `login` varchar(190) NOT NULL DEFAULT '',
  `id_site` int NOT NULL DEFAULT '0',
  `idgroupes` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`login`,`id_site`)
);
CREATE TABLE `grr_j_group_site` (
  `idgroupes` int NOT NULL,
  `id_site` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`idgroupes`,`id_site`)
);
ALTER TABLE `grr_site` ADD COLUMN `access` char(1) DEFAULT 'a';