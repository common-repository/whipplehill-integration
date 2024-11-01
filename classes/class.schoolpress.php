<?php

class SP_DB_INSTALL{
	function do_it(){

		if(defined('SP_MASTER_DB_USER')){
		$spdb = new wpdb( SP_MASTER_DB_USER, SP_MASTER_DB_PASSWORD, DB_NAME, SP_MASTER_DB_HOST );
		$success = $spdb->query("
CREATE DEFINER=`".SP_MASTER_DB_USER."`@`%` PROCEDURE `blogs`(skip INT,cnt INT,prefix char(4))
BEGIN
    DECLARE blogid BIGINT;
    DECLARE done INT DEFAULT 0;

    DECLARE cur CURSOR FOR SELECT blog_id FROM temp_blog_ids LIMIT 0,cnt; 
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

	CREATE TEMPORARY TABLE temp_blog_ids(blog_id BIGINT NOT NULL);

	SET @s = concat('INSERT temp_blog_ids(blog_id)	SELECT blog_id FROM ',prefix,'_blogs');

	IF EXISTS 
	( 
		SELECT 1 
		FROM Information_schema.tables 
		WHERE 
		table_name = concat(prefix,'_blogs')
	) THEN 
			PREPARE stmt0 FROM @s;
			EXECUTE stmt0; 
			DEALLOCATE PREPARE stmt0;
	END IF;


	CREATE TEMPORARY TABLE temp_blogs(
		 blog_id BIGINT NOT NULL
		,blog_name LONGTEXT
		,site_url LONGTEXT
		,archived bit
		,deleted tinyint
		,domain varchar(200)
		,lang_id int 
		,last_updated datetime
		,mature tinyint
		,path varchar(100)
		,post_count int
		,public tinyint
		,registered datetime
		,site_id bigint
		,spam tinyint
	);

    OPEN cur;
    read_loop: LOOP 
    FETCH cur INTO blogid;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET @s = concat('INSERT temp_blogs(blog_id,blog_name,site_url
						,archived ,deleted, domain, lang_id	,last_updated,mature
						,path,post_count,public,registered,site_id,spam)
						 SELECT ',blogid,',T2.option_value , T3.option_value  
						,B.archived ,B.deleted, B.domain, B.lang_id	,B.last_updated,B.mature
						,B.path,T4.option_value,B.public,B.registered,B.site_id,B.spam 
						 FROM ',prefix,'_blogs B 
						 LEFT OUTER JOIN ',prefix,'_',blogid,'_options T2 ON T2.option_name = ''blogname''
						 LEFT OUTER JOIN ',prefix,'_',blogid,'_options T3 ON T3.option_name = ''siteurl'' 
						 LEFT OUTER JOIN ',prefix,'_',blogid,'_options T4 ON T4.option_name = ''post_count''
						 WHERE B.blog_id = ',blogid); 

		IF EXISTS 
		( 
			SELECT 1 
			FROM Information_schema.tables 
			WHERE 
			table_name = concat(prefix,'_',blogid,'_options')
			-- AND table_schema = concat('wh_',blogid,'_options')
		) THEN 
				PREPARE stmt1 FROM @s;
				EXECUTE stmt1; 
				DEALLOCATE PREPARE stmt1;
		END IF;
	
	END LOOP;
    CLOSE cur;

SELECT blog_id,blog_name,site_url 
	,archived ,deleted, domain, lang_id	,last_updated,mature
	,path,post_count,public,registered,site_id,spam
FROM temp_blogs T
LIMIT skip,cnt;

DROP TABLE temp_blogs;
DROP TABLE temp_blog_ids;
END;;
");

	
	if($success != false ){
		return true;
	}
}
	return false;


	}
}