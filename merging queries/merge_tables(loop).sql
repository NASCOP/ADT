DROP PROCEDURE IF EXISTS mergeTable;

DELIMITER $$

CREATE PROCEDURE mergeTable(
    IN source_schema VARCHAR(100),
    IN destination_schema VARCHAR(100),
    IN source_table VARCHAR(100),
    IN destination_table VARCHAR(100),

    IN pk_col VARCHAR(100),
    IN fk_col VARCHAR(100),
    IN fk_name_col VARCHAR(100)
) 
BEGIN
set @source_schema_name = source_schema;
set @destination_schema_name = destination_schema;
set @source_table_name = source_table;
set @fk_table_col_name = fk_name_col;
-- set @destination_table_name = destination_table;
-- set @pk_column = pk_column;
-- set @fk_column = fk_column;



/* Create the full statement to execute */
set @migratestmt = concat('insert into ',@destination_schema_name,'.',@source_table_name,' select * from ',@source_schema_name,'.',@source_table_name,' union  select * from ',@destination_schema_name,'.',@source_table_name,';');

/* Prepare and execute the statement that was built */
prepare DynamicStatement from @migratestmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;




CREATE TABLE tmp_table (id int  AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100));

/* Create the full statement to execute */
set @tmpinsert = concat('insert into tmp_table (name)select distinct ',@fk_table_col_name,' from ',@source_table_name,';');

/* Prepare and execute the statement that was built */
prepare DynamicStatement from @tmpinsert ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;
 

SET @dest_tbl_array = destination_table;
SET @fk_array = fk_col;


 -- loop should begin here

WHILE (LOCATE(',', @dest_tbl_array) > 0)
DO

SET @destination_table_name  = ELT(1, @dest_tbl_array );
SET @fk_column				 = ELT(1, @fk_array);

SET @dest_tbl_array = SUBSTRING(@dest_tbl_array, LOCATE(',',@dest_tbl_array) + 1);
SET @fk_array		= SUBSTRING(@fk_array, LOCATE(',',@fk_array) + 1);

    
    /* Create the full statement to execute */
set @alterdeststmt = concat ('alter table ',@destination_table_name,' change ',@fk_column,' ', @fk_column,' varchar(100);');
/* Prepare and execute the statement that was built */
prepare DynamicStatement from @alterdeststmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;

    /* Create the full statement to execute */
set @updatedeststmt = concat ('update ',@destination_table_name,', tmp_table set ',@destination_table_name,'.',@fk_column,' = tmp_table.name where tmp_table.id = ',@destination_table_name,'.',@fk_column,';');
/* Prepare and execute the statement that was built */
prepare DynamicStatement from @updatedeststmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;

    /* Create the full statement to execute */
set @updatedestidstmt = concat ('update ',@destination_table_name,', tmp_table set ',@destination_table_name,'.',@fk_column,' = tmp_table.id where tmp_table.name = ',@destination_table_name,'.',@fk_column,';');
/* Prepare and execute the statement that was built */
prepare DynamicStatement from @updatedestidstmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;

    /* Create the full statement to execute */
set @alterdestintstmt = concat ('alter table ',@destination_table_name,' change ',@fk_column,' ',@fk_column,' int(20);');
/* Prepare and execute the statement that was built */
prepare DynamicStatement from @alterdestintstmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
END WHILE;
-- end loop here

    /* Create the full statement to execute */
set @truncatesrcstmt = concat ('truncate ',@source_table_name,';');
/* Prepare and execute the statement that was built */
prepare DynamicStatement from @truncatesrcstmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;

    /* Create the full statement to execute */
set @insertsrcstmt = concat ('insert into ',@source_table_name,'(id,',@fk_table_col_name,') select id,name  from tmp_table ;');
/* Prepare and execute the statement that was built */
prepare DynamicStatement from @insertsrcstmt ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;



DROP table tmp_table;

END $$

DELIMITER ;