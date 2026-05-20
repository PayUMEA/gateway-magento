# Changelog

## [0.5.2] - 2026-05-19

### Changed
- Refactored `CheckTransactionStatus` cron to use `AcceptPaymentOperation` and `DenyPaymentOperation` for better order state management.
- Improved payment method detail logging in order comments during transaction status checks.
- Updated `CancelHandler` to support `check_transaction_status` flag and improved message handling.
- Enhanced `VoidHandler` to correctly set parent transaction ID.
- Switched `PayUAdapter` log level to `INFO` for production environments.
- Optimized `TransferObject` state checks using `TransactionState` enum cases.
- Improved admin UI for transaction data retrieval with better scoping of JS variables and HTML IDs.

### Fixed
- Issue with transaction ID mapping in `AbstractOperation` and `VoidHandler`.
- Transaction state validation logic in `TransferObject`.
- Case-sensitivity and type-safety in `TransactionState` enum usage.

## [0.5.1] - 2026-03-08

### Added
- Adminhtml refund handler and response validator
- UI component configuration options for Lock and Status management
- Transaction search capabilities using FilterBuilder and SearchCriteriaBuilder
- Detailed payment information to order comments including payment type
- Transaction type information to failed payment notifications

### Changed
- Improved exception handling in notification processing with proper error logging
- Enhanced capture strategy command with better transaction handling logic
- Updated payment operations with readonly properties for better immutability
- Replaced old refund handler with new Adminhtml refund handler in DI configuration
- Refactored CancelHandler to use TransactionUpdateOperation instead of direct payment repository
- Updated Processor methods with improved parameter handling and type hints
- Enhanced IPN notification processing logic to check for existing invoices

### Fixed
- Success message handling for credited orders in Magento
- Transaction update operations to properly handle order states during refund/credit scenarios
- Issue with order ID validation in CancelHandler

### Removed
- Old RefundHandler in favor of Adminhtml RefundHandler
- Unused TransactionState constant references
- OrderPaymentRepositoryInterface dependency from CancelHandler
