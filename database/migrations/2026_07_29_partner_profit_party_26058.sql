-- Remap import partner profit from Party Master 26014 → 26058
-- (Muhammad Faisal ( Partner ) — dedicated partner-profit ledger)
-- Run on deployed DB after config IMPORT_PARTNER_PARTY_CODE = '26058'.

-- Open accruals (Partner due)
UPDATE import_payable_accruals ipa
INNER JOIN parties oldp ON oldp.id = ipa.party_id AND oldp.party_code = '26014'
INNER JOIN parties newp ON newp.party_code = '26058' AND newp.is_active = 1
SET ipa.party_id = newp.id
WHERE ipa.leg = 'partner' AND ipa.status = 'open';

-- Item charge partner link (new receives / display)
UPDATE shipment_item_charges sic
INNER JOIN parties oldp ON oldp.id = sic.partner_party_id AND oldp.party_code = '26014'
INNER JOIN parties newp ON newp.party_code = '26058' AND newp.is_active = 1
SET sic.partner_party_id = newp.id
WHERE sic.partner_party_id IS NOT NULL;

-- Legacy monthly shipment_costs rows still unpaid
UPDATE shipment_costs sc
INNER JOIN parties oldp ON oldp.id = sc.partner_party_id AND oldp.party_code = '26014'
INNER JOIN parties newp ON newp.party_code = '26058' AND newp.is_active = 1
SET sc.partner_party_id = newp.id
WHERE sc.pay_timing = 'monthly' AND sc.payment_id IS NULL;

-- Active partner-profit PAYs still on old party (moves statement to 26058)
UPDATE payments p
INNER JOIN parties oldp ON oldp.id = p.party_id AND oldp.party_code = '26014'
INNER JOIN parties newp ON newp.party_code = '26058' AND newp.is_active = 1
SET p.party_id = newp.id
WHERE p.ref_type = 'shipment_partner' AND p.status = 'active';

-- Paid accruals linked to those PAYs (keep party_id in sync)
UPDATE import_payable_accruals ipa
INNER JOIN parties oldp ON oldp.id = ipa.party_id AND oldp.party_code = '26014'
INNER JOIN parties newp ON newp.party_code = '26058' AND newp.is_active = 1
SET ipa.party_id = newp.id
WHERE ipa.leg = 'partner' AND ipa.status = 'paid';
