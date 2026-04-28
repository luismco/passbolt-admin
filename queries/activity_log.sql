SELECT
  al.id AS log_id,
  SUBSTRING_INDEX(sharer.username, '@', 1) AS 'User',
  CASE ph.aco
    WHEN 'Resource' THEN 'Password'
    WHEN 'Folder'   THEN 'Folder'
  END AS resource_type,
  ph.aco_foreign_key AS resource_id,
  COALESCE(r.name, f.name, CONCAT('[deleted] ', ph.aco_foreign_key)) AS 'Resource Name',
  CONCAT(
    SUBSTRING_INDEX(COALESCE(u.username, g.name), '@', 1),
    ' (',
    ph.aro,
    ')'
  ) AS 'Shared With',
  CASE eh.crud
    WHEN 'c' THEN 'Share'
    WHEN 'u' THEN 'Permission update'
    WHEN 'd' THEN 'Revoke'
  END AS 'Action',
  al.created AS 'Modified at'
FROM action_logs al
JOIN actions a
  ON a.id = al.action_id
JOIN entities_history eh
  ON eh.action_log_id = al.id
JOIN permissions_history ph
  ON ph.id = eh.foreign_key
LEFT JOIN resources r
  ON ph.aco = 'Resource'
 AND r.id = ph.aco_foreign_key
LEFT JOIN folders f
  ON ph.aco = 'Folder'
 AND f.id = ph.aco_foreign_key
LEFT JOIN users u
  ON ph.aro = 'User'
 AND u.id = ph.aro_foreign_key
LEFT JOIN groups g
  ON ph.aro = 'Group'
 AND g.id = ph.aro_foreign_key
JOIN users sharer
  ON sharer.id = al.user_id
ORDER BY al.created DESC;
