ALTER TABLE `ledger`
  ADD COLUMN `DeceasedName` VARCHAR(255) DEFAULT NULL AFTER `Payee`;

-- Optional backfill: set ledger.DeceasedName from deceased table where apt matches nicheID
-- This will concatenate first/middle/last (skips empty parts). Run carefully (backup first).
UPDATE ledger l
JOIN deceased d ON TRIM(l.ApartmentNo) = TRIM(d.nicheID)
SET l.DeceasedName = TRIM(CONCAT_WS(' ',
    NULLIF(d.firstName, ''),
    NULLIF(d.middleName, ''),
    NULLIF(d.lastName, '')
))
WHERE (l.DeceasedName IS NULL OR l.DeceasedName = '');

-- If you prefer to pull names from assessment table (informant mapping) for entries without niche match:
UPDATE ledger l
JOIN assessment a ON TRIM(l.Payee) = TRIM(a.informant_name)
SET l.DeceasedName = COALESCE(l.DeceasedName, a.deceased_name)
WHERE (l.DeceasedName IS NULL OR l.DeceasedName = '');
