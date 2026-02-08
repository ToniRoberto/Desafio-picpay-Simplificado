CREATE TABLE IF NOT EXISTS transfers (
    id SERIAL PRIMARY KEY,
    payer_id INTEGER NOT NULL,
    payee_id INTEGER NOT NULL,
    value DECIMAL(15, 2) NOT NULL CHECK (value > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'reverted')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transfers_payer FOREIGN KEY (payer_id) REFERENCES users(id),
    CONSTRAINT fk_transfers_payee FOREIGN KEY (payee_id) REFERENCES users(id)
);

CREATE INDEX idx_transfers_payer ON transfers(payer_id);
CREATE INDEX idx_transfers_payee ON transfers(payee_id);
CREATE INDEX idx_transfers_status ON transfers(status);
CREATE INDEX idx_transfers_created_at ON transfers(created_at);

