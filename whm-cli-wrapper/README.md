# WHM CLI Wrapper

This project provides a command-line interface (CLI) wrapper for creating cPanel/WHM accounts using the WHM API. It simplifies the process of account creation by allowing users to interact with the existing PHP script through command-line arguments.

## Project Structure

```
whm-cli-wrapper
├── bin
│   └── whm-cli.php          # Command-line interface for account creation
├── src
│   └── create-whm-account.php # Existing PHP script for creating WHM accounts
├── composer.json             # Composer configuration file
├── .env.example              # Example environment configuration
└── README.md                 # Project documentation
```

## Requirements

- PHP 7.2 or higher
- Composer

## Installation

1. Clone the repository:

   ```
   git clone <repository-url>
   cd whm-cli-wrapper
   ```

2. Install dependencies using Composer:

   ```
   composer install
   ```

3. Create a `.env` file based on the `.env.example` file and fill in the required environment variables:

   ```
   cp .env.example .env
   ```

## Usage

To create a new cPanel/WHM account, use the following command:

```
php bin/whm-cli.php --domain example.com --user username --password yourpassword --package yourpackage --email user@example.com
```

### Options

- `--domain`: The domain or subdomain for the new account (required).
- `--user`: The username for the new account (1-16 lowercase letters/numbers, required).
- `--password`: The password for the new account (optional, leave blank to auto-generate).
- `--package`: The hosting package name (required).
- `--email`: The contact email for the account (required).

## Example

To create an account with the domain `example.com`, username `user123`, and package `basic`, run:

```
php bin/whm-cli.php --domain example.com --user user123 --package basic --email user@example.com
```

## License

This project is licensed under the MIT License. See the LICENSE file for more details.