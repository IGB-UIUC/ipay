CREATE TABLE  tbl_creditcard (
	creditcard_referenceId INT NOT NULL AUTO_INCREMENT,
	creditcard_transactionId varchar(13),
	creditcard_token varchar(48),
	creditcard_paymentAmount DECIMAL(7,2),
	creditcard_recAccount VARCHAR(25),
	creditcard_recAmount DeCIMAL(7,2),
	creditcard_statusId INT REFERENCES tbl_status(status_id),
	creditcard_payDate DATETIME,
	creditcard_registrationId INT REFERENCES tbl_registration(registration_id),
	PRIMARY KEY  (creditcard_referenceId)
);
