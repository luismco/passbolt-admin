SELECT
  fr.foreign_model AS item_type,
  fr.foreign_id    AS item_id,
  CASE fr.foreign_model
    WHEN 'Resource' THEN r.name
    WHEN 'Folder'   THEN f.name
  END AS item_name
FROM (
  SELECT DISTINCT foreign_model, foreign_id
  FROM folders_relations
  WHERE folder_parent_id = :folder_id
) fr
LEFT JOIN resources r
  ON fr.foreign_model = 'Resource'
 AND r.id = fr.foreign_id
LEFT JOIN folders f
  ON fr.foreign_model = 'Folder'
 AND f.id = fr.foreign_id
ORDER BY fr.foreign_model DESC, item_name ASC;
-- ORDER: Folder first (DESC on 'Resource'/'Folder' alphabetically gives Folder first), then by name
