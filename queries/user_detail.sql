SELECT
  CASE p.aco
    WHEN 'Resource' THEN 'password'
    WHEN 'Folder'   THEN 'folder'
  END AS resource_type,
  COALESCE(r.name, f.name) AS resource_name,
  CASE p.type
    WHEN 1  THEN 'Read'
    WHEN 7  THEN 'Update'
    WHEN 15 THEN 'Owner'
  END AS permission,
  CASE
    -- ownership is always direct
    WHEN p.type = 15
      THEN 'Direct'
    -- folder permission: always direct (the folder itself was shared)
    WHEN p.aro = 'User' AND p.aco = 'Folder'
      THEN 'Folder Share'
    -- group-based permission
    WHEN p.aro = 'Group'
      THEN CONCAT('Group: ', g.name)
    -- direct resource permission: check if the resource lives inside
    -- a folder the user also has explicit permission on.
    -- If so, the real source is the folder share, not a direct password share.
    WHEN p.aro = 'User' AND p.aco = 'Resource'
      THEN COALESCE(
        (
          SELECT CONCAT('Folder: ', pf_name.folder_name)
          FROM (
            SELECT
              fp.aco_foreign_key AS folder_id,
              ff.name            AS folder_name
            FROM permissions fp
            JOIN folders ff
              ON ff.id = fp.aco_foreign_key
            WHERE fp.aro = 'User'
              AND fp.aro_foreign_key = :user_id
              AND fp.aco = 'Folder'
          ) pf_name
          JOIN folders_relations fr
            ON fr.folder_parent_id = pf_name.folder_id
           AND fr.foreign_model    = 'Resource'
           AND fr.foreign_id       = p.aco_foreign_key
           AND fr.user_id          = :user_id
          LIMIT 1
        ),
        'Password Share'
      )
  END AS access_via,
  p.modified AS last_modified
FROM permissions p
LEFT JOIN users u
  ON p.aro = 'User'
 AND u.id = p.aro_foreign_key
LEFT JOIN groups g
  ON p.aro = 'Group'
 AND g.id = p.aro_foreign_key
LEFT JOIN groups_users gu
  ON gu.group_id = g.id
 AND gu.user_id = :user_id
LEFT JOIN resources r
  ON p.aco = 'Resource'
 AND r.id = p.aco_foreign_key
LEFT JOIN folders f
  ON p.aco = 'Folder'
 AND f.id = p.aco_foreign_key
WHERE
  (
    (p.aro = 'User'  AND p.aro_foreign_key = :user_id)
    OR
    (p.aro = 'Group' AND gu.user_id IS NOT NULL)
  )
ORDER BY resource_type, resource_name;
