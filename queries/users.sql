-- Users list with total accessible password count
WITH

-- 1. Direct resource permissions (User -> Resource)
direct_resources AS (
    SELECT
        p.aro_foreign_key AS user_id,
        p.aco_foreign_key AS resource_id
    FROM permissions p
    WHERE p.aro = 'User'
      AND p.aco = 'Resource'
),

-- 2. Resources accessible via group membership
group_resources AS (
    SELECT
        gu.user_id,
        p.aco_foreign_key AS resource_id
    FROM permissions p
    JOIN groups_users gu
      ON gu.group_id = p.aro_foreign_key
    WHERE p.aro = 'Group'
      AND p.aco = 'Resource'
),

-- 3. Folders the user has direct permission to
user_folders AS (
    SELECT
        p.aro_foreign_key AS user_id,
        p.aco_foreign_key AS folder_id
    FROM permissions p
    WHERE p.aro = 'User'
      AND p.aco = 'Folder'
),

-- 4. Folders accessible via group membership
group_folders AS (
    SELECT
        gu.user_id,
        p.aco_foreign_key AS folder_id
    FROM permissions p
    JOIN groups_users gu
      ON gu.group_id = p.aro_foreign_key
    WHERE p.aro = 'Group'
      AND p.aco = 'Folder'
),

-- 5. All folders a user can access (direct + group)
all_user_folders AS (
    SELECT user_id, folder_id FROM user_folders
    UNION
    SELECT user_id, folder_id FROM group_folders
),

-- 6. Resources inside folders the user can access
--    folders_relations.user_id scopes relations per user
folder_resources AS (
    SELECT
        auf.user_id,
        fr.foreign_id AS resource_id
    FROM all_user_folders auf
    JOIN folders_relations fr
      ON fr.folder_parent_id = auf.folder_id
     AND fr.foreign_model    = 'Resource'
     AND fr.user_id          = auf.user_id
),

-- 7. Union all access paths and deduplicate per user
all_accessible AS (
    SELECT user_id, resource_id FROM direct_resources
    UNION
    SELECT user_id, resource_id FROM group_resources
    UNION
    SELECT user_id, resource_id FROM folder_resources
)

SELECT
    u.id                                          AS id,
    SUBSTRING_INDEX(u.username, '@', 1)           AS username,
    COUNT(DISTINCT aa.resource_id)                AS total_passwords,
    u.last_logged_in
FROM users u
LEFT JOIN all_accessible aa ON aa.user_id = u.id
WHERE u.deleted = 0
GROUP BY u.id, u.username, u.last_logged_in
ORDER BY total_passwords DESC;
