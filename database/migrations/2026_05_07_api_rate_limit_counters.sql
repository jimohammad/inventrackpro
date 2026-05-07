-- API rate limit counters (fixed 60s windows)
-- Apply this on the deployed DB once.
--
-- Rationale:
-- - Avoid CREATE TABLE on every API request (metadata locks under load)
-- - Avoid phantom-read race by serializing per-key window counters

CREATE TABLE IF NOT EXISTS api_rate_limit_counters (
  api_key_id INT NOT NULL,
  window_key INT NOT NULL, -- floor(UNIX_TIMESTAMP()/60)
  cnt INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (api_key_id, window_key),
  KEY idx_window (window_key)
);

