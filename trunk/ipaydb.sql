CREATE TABLE  ipay (
	ipay_id INT NOT NULL AUTO_INCREMENT,
	ipay_registration_id INT,
	ipay_transaction_id VARCHAR(13),
	ipay_token VARCHAR(48),
	ipay_payment_amount DECIMAL(7,2),
	ipay_rec_account VARCHAR(25),
	ipay_rec_amount DECIMAL(7,2),
	ipay_date DATETIME,
	ipay_status ENUM('Pending','Canceled','Paided','Error'),
	ipay_error_code INT,
	ipay_error_msg VARCHAR(30),
	PRIMARY KEY  (ipay_id)
);
