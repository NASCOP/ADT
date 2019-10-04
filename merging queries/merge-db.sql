DROP PROCEDURE IF EXISTS mergeDB;

DELIMITER $$

CREATE PROCEDURE mergeDB(
    IN source_schema VARCHAR(100),
    IN destination_schema VARCHAR(100)
) 
BEGIN

-- generic_name
set @source_schema_name = source_schema;
set @destination_schema_name = destination_schema;

/* Create the full statement to execute */
set @StatementToExecute = concat('insert into ',@destination_schema_name,'.generic_name  select * from ',@source_schema_name,'.generic_name union  select * from ',@destination_schema_name,'.generic_name;');

/* Prepare and execute the statement that was built */
prepare DynamicStatement from @StatementToExecute ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;

CREATE TABLE tmp_table (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

/* Create the full statement to execute */
set @StatementToExecute = concat('insert into tmp_table (name )select distinct name from ',@destination_schema_name,'.generic_name;');

/* Prepare and execute the statement that was built */
prepare DynamicStatement from @StatementToExecute ;
execute DynamicStatement ;
/* Cleanup the prepared statement */
deallocate prepare DynamicStatement;


    alter table drugcode change generic_name generic_name varchar(100);
    update drugcode, tmp_table set drugcode.generic_name = tmp_table.name where tmp_table.id = drugcode.generic_name;
    update drugcode, tmp_table set drugcode.generic_name = tmp_table.id where tmp_table.name = drugcode.generic_name;
    truncate generic_name;
    insert into generic_name(id,name) select id,name  from tmp_table ;
    alter table drugcode change generic_name generic_name int(20);
    DROP table tmp_table;

END $$

DELIMITER ;