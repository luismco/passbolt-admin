SELECT
  CONCAT(
    SUBSTRING_INDEX(COALESCE(u.username, g.name), '@', 1),
    ' (',
    p.aro,
    ')'
  ) AS 'Shared with',
  CASE p.type
    WHEN 1  THEN 'Read'
    WHEN 7  THEN 'Update'
    WHEN 15 THEN 'Owner'
  END AS 'Permission',
  p.modified AS 'Last Modified'
FROM permissions p
LEFT JOIN users u
  ON p.aro = 'User'
 AND u.id = p.aro_foreign_key
LEFT JOIN groups g
  ON p.aro = 'Group'
 AND g.id = p.aro_foreign_key
WHERE p.aco = 'Folder'
  AND p.aco_foreign_key = :folder_id
ORDER BY p.modified DESC;
