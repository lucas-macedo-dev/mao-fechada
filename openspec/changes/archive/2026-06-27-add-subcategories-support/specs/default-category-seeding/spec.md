## MODIFIED Requirements

### Requirement: New users receive default categories on registration
The system SHALL automatically create a predefined set of expense and income categories for every new user immediately after their account is created. Categories SHALL be locale-aware: users with locale `pt-BR` receive Portuguese names; all other locales receive English names.

**Default expense categories (type = expense):**
| pt-BR | en |
|---|---|
| Veículo | Vehicle |
| Habitação | Housing |
| Alimentação | Food |
| Compras | Shopping |
| Lazer | Leisure |
| Sem Categoria | No Category |

**Default income categories (type = income):**
| pt-BR | en |
|---|---|
| Salário | Salary |
| Investimentos | Investments |
| Ticket Alimentação | Food Vouchers |
| Sem Categoria | No Category |

#### Scenario: New user with pt-BR locale gets Portuguese default categories
- **WHEN** a new user registers with `locale = pt-BR`
- **THEN** the system SHALL create 6 expense categories named Veículo, Habitação, Alimentação, Compras, Lazer, Sem Categoria and 4 income categories named Salário, Investimentos, Ticket Alimentação, Sem Categoria — all owned by that user

#### Scenario: New user with en locale gets English default categories
- **WHEN** a new user registers with `locale = en`
- **THEN** the system SHALL create 6 expense categories named Vehicle, Housing, Food, Shopping, Leisure, No Category and 4 income categories named Salary, Investments, Food Vouchers, No Category — all owned by that user

#### Scenario: New user with no locale defaults to English categories
- **WHEN** a new user registers without specifying a locale
- **THEN** the system SHALL create the English default categories including the "No Category" fallbacks
