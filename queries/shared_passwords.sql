SELECT
  r.id AS 'id',
  r.name AS 'Password Name',
  SUM(p.aro = 'User')  AS 'Users',
  SUM(p.aro = 'Group') AS 'Groups',
  MAX(p.modified) AS 'Last Modified'
FROM permissions p
JOIN resources r
  ON p.aco = 'Resource'
 AND r.id = p.aco_foreign_key
GROUP BY r.id, r.name
HAVING COUNT(*) > 1
ORDER BY MAX(p.modified) DESC;
