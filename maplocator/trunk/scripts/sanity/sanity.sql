------------- Meta_Layer --------------
-- summary_columns empty check
select layer_tablename, layer_name from "Meta_Layer" where (summary_columns is null or summary_columns = '');

-- editable_columns empty check
select layer_tablename, layer_name from "Meta_Layer" where access in (1, 3) and (editable_columns is null or editable_columns = '');

-- search_columns empty check
select layer_tablename, layer_name from "Meta_Layer" where (search_columns is null or search_columns = '');

-- filter_columns empty check
select layer_tablename, layer_name from "Meta_Layer" where is_filterable = 1 and (filter_columns is null or filter_columns = '');

-- title_columns empty check
select layer_tablename, layer_name from "Meta_Layer" where (title_column is null or title_column = '');
------------- end Meta_Layer --------------

------------- Meta_LinkTable --------------
-- summary_columns empty check
select link_tablename, link_name from "Meta_LinkTable" where (summary_columns is null or summary_columns = '');

-- editable_columns empty check
select link_tablename, link_name from "Meta_LinkTable" where access in (1, 3) and (editable_columns is null or editable_columns = '');

-- filter_columns empty check
select link_tablename, link_name from "Meta_LinkTable" where is_filterable = 1 and (filter_columns is null or filter_columns = '');

-- linked_column or layer_column empty check
select mlt.link_name, mlt.link_tablename, mlt.linked_column, ml.layer_name, ml.layer_tablename, mlt.layer_column
  from "Meta_LinkTable" mlt left join "Meta_Layer" ml on mlt.layer_id = ml.layer_id
  where (mlt.linked_column is null or mlt.linked_column = '') or (mlt.layer_column is null or mlt.layer_column = '');
------------- end Meta_LinkTable --------------

------------- Meta_Global_Resource --------------
-- displayed_columns empty check
select resource_tablename from "Meta_Global_Resource" where (displayed_columns is null or displayed_columns = '');
------------- end Meta_Global_Resource --------------

------------ Theme_Layer_Mapping -------------------
-- remove all layer_id which are not in current layer table.
delete from "Theme_Layer_Mapping" where layer_id in
  (select layer_id from "Theme_Layer_Mapping" where layer_id not in (select layer_id from "Meta_Layer") group by layer_id order by layer_id);

-- find layers which have not been added to theme mapping table.
select layer_id, layer_name, layer_tablename from "Meta_Layer" where layer_id not in
  (select distinct layer_id from "Theme_Layer_Mapping");

-- find layers which have not been added to Theme_Layer_Mapping
select layer_name, layer_tablename from "Meta_Layer" where layer_id not in (SELECT distinct layer_id  FROM "Theme_Layer_Mapping");

-- find layers which do not have exactly 2 theme mappings.
select layer_id, layer_name, layer_tablename from "Meta_Layer" where layer_id in
  (SELECT layer_id FROM "Theme_Layer_Mapping" group by layer_id having count(*) != 2);
------------ end Theme_Layer_Mapping -------------------
