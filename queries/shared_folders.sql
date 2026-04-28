WITH RECURSIVE folder_tree AS (
    SELECT
        f.id AS root_folder_id,
        f.id AS folder_id
    FROM folders f
    UNION ALL
    SELECT
        ft.root_folder_id,
        fr_child.foreign_id AS folder_id
    FROM folder_tree ft
    JOIN folders_relations fr_child
      ON fr_child.folder_parent_id = ft.folder_id
     AND fr_child.foreign_model = 'Folder'
),
recursive_password_count AS (
    SELECT
        ft.root_folder_id,
        COUNT(DISTINCT fr_res.foreign_id) AS passwords_recursive
    FROM folder_tree ft
    JOIN folders_relations fr_res
      ON fr_res.folder_parent_id = ft.folder_id
     AND fr_res.foreign_model = 'Resource'
    GROUP BY ft.root_folder_id
)
SELECT
    f.id AS 'id',
    f.name AS 'Folder Name',
    SUM(p.aro = 'User')  AS 'Users',
    SUM(p.aro = 'Group') AS 'Groups',
    COALESCE(rpc.passwords_recursive, 0) AS 'Number of Password (Recursive)',
    MAX(p.modified) AS 'Last Modified'
FROM permissions p
JOIN folders f
  ON p.aco = 'Folder'
 AND f.id = p.aco_foreign_key
LEFT JOIN recursive_password_count rpc
  ON rpc.root_folder_id = f.id
GROUP BY f.id, f.name, rpc.passwords_recursive
HAVING COUNT(*) > 1
ORDER BY MAX(p.modified) DESC;
