<?php
/**
 * Remote Backup
 *
 * Creates a backup of wp-content, config.php, and database.sql.
 *
 * @package V_WP_Dashboard
 * @since 1.0.2
 */

namespace VWPDashboard\Services;

use VWPDashboard\Helpers\Options;
use VWPDashboard\Helpers\Pushover;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use WP_Error;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RemoteBackup
 *
 * Handles creating and managing remote backups.
 *
 * @since 1.0.2
 */
class RemoteBackup {

	/**
	 * Validate and quote an SQL identifier (table/column).
	 *
	 * Uses a conservative whitelist: only letters, numbers, and underscores.
	 * Returns a backticked identifier on success, or an empty string on failure.
	 *
	 * @since 1.0.3
	 * @param string $identifier Raw identifier.
	 * @return string Backticked identifier or empty string if invalid.
	 */
	private function sanitize_identifier( string $identifier ): string {
		if ( '' === $identifier ) {
			return '';
		}

		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $identifier ) ) {
			return '';
		}

		return '`' . $identifier . '`';
	}

	/**
	 * Creates a backup and sends it to the remote server.
	 *
	 * Returns a WP_Error object on failure to avoid terminating the request.
	 *
	 * @since 1.0.2
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function create_backup() {
		if ( ! $this->validate_prerequisites() ) {
			return new WP_Error( 'missing_requirements', 'Missing required functions or constants' );
		}

		$backup_file = $this->create_backup_zip();
		if ( is_wp_error( $backup_file ) ) {
			return $backup_file;
		}

		$transfer_result = $this->transfer_backup( $backup_file );
		if ( is_wp_error( $transfer_result ) ) {
			wp_delete_file( $backup_file );
			return $transfer_result;
		}

		wp_delete_file( $backup_file );
		$this->manage_remote_backups();

		Pushover::send( 'Backup created and transferred successfully.', 1 );
		return true;
	}

	/**
	 * Validate prerequisites for backup creation.
	 *
	 * @since 1.0.2
	 * @return bool
	 */
	private function validate_prerequisites(): bool {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$message = 'ZipArchive class not available';
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return false;
		}

		$required_options = array( 'ssh_host', 'ssh_port', 'ssh_user', 'ssh_password', 'remote_backup_dir' );
		foreach ( $required_options as $option ) {
			$value = Options::get( $option );
			if ( empty( $value ) ) {
				$message = sprintf( 'Required option %s is not set', $option );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $message );
				}
				return false;
			}
		}

		$ssh_functions = array( 'ssh2_connect', 'ssh2_auth_password', 'ssh2_scp_send', 'ssh2_sftp', 'ssh2_sftp_unlink' );
		foreach ( $ssh_functions as $func ) {
			if ( ! function_exists( $func ) ) {
				$message = sprintf( '%s function not available', $func );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $message );
				}
				return false;
			}
		}

		return true;
	}

	/**
	 * Create backup zip file.
	 *
	 * @since 1.0.2
	 * @return string|WP_Error Path to backup file on success, WP_Error on failure.
	 * @throws Exception Thrown when an internal step fails and is escalated.
	 */
	private function create_backup_zip() {
		$zip = new ZipArchive();

		$upload_dir  = wp_upload_dir();
		$backup_base = is_array( $upload_dir ) && empty( $upload_dir['error'] ) ? rtrim( $upload_dir['path'], '/\\' ) : sys_get_temp_dir();
		$backup_file = $backup_base . '/v-backup-' . gmdate( 'Ymd-His' ) . '.zip';

		if ( true !== $zip->open( $backup_file, ZipArchive::CREATE ) ) {
			$message = "Cannot open <{$backup_file}>";
			Pushover::send( 'Backup creation failed: Cannot open zip file.', 1 );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return new WP_Error( 'backup_zip_open_failed', $message );
		}

		try {
			$this->add_wp_content_to_zip( $zip );
			$this->add_config_to_zip( $zip );

			$sql_result = $this->add_database_to_zip( $zip );
			if ( is_wp_error( $sql_result ) ) {
				throw new Exception( $sql_result->get_error_message() );
			}
		} catch ( Exception $e ) {
			$zip->close();
			if ( file_exists( $backup_file ) ) {
				wp_delete_file( $backup_file );
			}
			return new WP_Error( 'backup_zip_failed', $e->getMessage() );
		}

		$zip->close();
		return $backup_file;
	}

	/**
	 * Add wp-content folder to zip.
	 *
	 * @since 1.0.2
	 * @param ZipArchive $zip Zip archive object.
	 * @return void
	 */
	private function add_wp_content_to_zip( ZipArchive $zip ): void {
		$wp_content = rtrim( WP_CONTENT_DIR, '/\\' );
		$base_name  = basename( $wp_content );
		if ( '' === $base_name ) {
			$base_name = 'wp-content';
		}

		$exclude = array(
			'cache',
			'w3tc-cache',
			'litespeed',
			'updraft',
			'ai1wm-backups',
			'backup',
			'backups',
			'ewww',
			'upgrade',
		);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $wp_content, FilesystemIterator::SKIP_DOTS )
		);

				/**
				 * Current file iteration wrapper.
				 *
				 * @var SplFileInfo $file
				 */
		foreach ( $iterator as $file ) {
			if ( $file->isDir() ) {
				continue;
			}

			$full_path = $file->getPathname();
			$relative  = ltrim( str_replace( '\\', '/', substr( $full_path, strlen( $wp_content ) ) ), '/' );
			$skip      = false;

			foreach ( $exclude as $prefix ) {
				if ( 0 === strpos( $relative, $prefix ) ) {
					$skip = true;
					break;
				}
			}

			if ( $skip ) {
				continue;
			}

			$zip->addFile( $full_path, $base_name . '/' . $relative );
		}
	}

	/**
	 * Add config file to zip.
	 *
	 * @since 1.0.2
	 * @param ZipArchive $zip Zip archive object.
	 * @return void
	 */
	private function add_config_to_zip( ZipArchive $zip ): void {
		$config_file = ABSPATH . 'wp-config.php';
		if ( file_exists( $config_file ) ) {
			$zip->addFile( $config_file, 'wp-config.php' );
		}
	}

	/**
	 * Add database export to zip.
	 *
	 * @since 1.0.2
	 * @param ZipArchive $zip Zip archive object.
	 * @return true|WP_Error
	 */
	private function add_database_to_zip( ZipArchive $zip ) {
		$upload_dir = wp_upload_dir();
		$base_dir   = is_array( $upload_dir ) && empty( $upload_dir['error'] ) ? rtrim( $upload_dir['path'], '/\\' ) : sys_get_temp_dir();
		$sql_file   = $base_dir . '/database-' . wp_generate_uuid4();

		$export_result = $this->export_database_wpdb( $sql_file );
		if ( is_wp_error( $export_result ) ) {
			Pushover::send( 'Backup creation failed: Database export error.', 1 );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Database export failed: ' . $export_result->get_error_message() );
			}
			return $export_result;
		}

		$sql_file_gz = $sql_file . '.gz';
		if ( file_exists( $sql_file_gz ) ) {
			$zip->addFile( $sql_file_gz, 'database.sql.gz' );
			wp_delete_file( $sql_file_gz );
		} elseif ( file_exists( $sql_file ) ) {
			$zip->addFile( $sql_file, 'database.sql' );
			wp_delete_file( $sql_file );
		}

		return true;
	}

	/**
	 * Export database using WordPress native functions instead of mysqldump.
	 *
	 * @since 1.0.2
	 * @param string $sql_file Path to the SQL file to write.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function export_database_wpdb( string $sql_file ) {
		global $wpdb;

		if ( ! function_exists( 'wp_delete_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$use_compression = function_exists( 'gzopen' );
		$target_file     = $use_compression ? $sql_file . '.gz' : $sql_file;

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming exports requires native handles.
		$handle = $use_compression ? gzopen( $target_file, 'w9' ) : fopen( $target_file, 'wb' );

		if ( false === $handle ) {
			return new WP_Error( 'sql_file_open_failed', 'Failed to open SQL file for writing.' );
		}

		try {
			$this->write_sql_chunk( $use_compression, $handle, $this->get_sql_header() );

			$tables_result = $this->export_database_tables( $use_compression, $handle );
			if ( is_wp_error( $tables_result ) ) {
				$this->close_sql_handle( $use_compression, $handle );
				if ( file_exists( $target_file ) ) {
					wp_delete_file( $target_file );
				}
				return $tables_result;
			}

			$this->write_sql_chunk( $use_compression, $handle, "COMMIT;\n" );
			$this->close_sql_handle( $use_compression, $handle );

			return true;
		} catch ( Exception $e ) {
			$this->close_sql_handle( $use_compression, $handle );
			if ( file_exists( $target_file ) ) {
				wp_delete_file( $target_file );
			}
			return new WP_Error( 'database_export_failed', 'Database export failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Get SQL header content.
	 *
	 * @since 1.0.2
	 * @return string
	 */
	private function get_sql_header(): string {
		$header  = "-- WordPress Database Export\n";
		$header .= '-- Generated on ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n";
		$header .= '-- Database: ' . DB_NAME . "\n\n";
		$header .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
		$header .= "SET AUTOCOMMIT = 0;\n";
		$header .= "START TRANSACTION;\n\n";
		return $header;
	}

	/**
	 * Write SQL content to file.
	 *
	 * @since 1.0.2
	 * @param bool   $use_compression Whether to use compression.
	 * @param mixed  $handle          File handle resource.
	 * @param string $content        Content to write.
	 * @return void
	 */
	private function write_sql_chunk( bool $use_compression, $handle, string $content ): void {
		if ( $use_compression ) {
			gzwrite( $handle, $content );
			return;
		}

		if ( is_resource( $handle ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Streaming exports requires native handles.
			fwrite( $handle, $content );
		}
	}

	/**
	 * Export database tables.
	 *
	 * @since 1.0.2
	 * @param bool  $use_compression Whether to use compression.
	 * @param mixed $handle          File handle resource.
	 * @return true|WP_Error
	 */
	private function export_database_tables( bool $use_compression, $handle ) {
		global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
		if ( empty( $tables ) ) {
			return new WP_Error( 'no_tables_found', 'No tables found in database.' );
		}

		foreach ( $tables as $table ) {
			$table_name         = $table[0];
			$escaped_table_name = $this->sanitize_identifier( $table_name );
			if ( '' === $escaped_table_name ) {
				continue;
			}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$create_table = $wpdb->get_row( "SHOW CREATE TABLE {$escaped_table_name}", ARRAY_N );
			if ( ! $create_table ) {
				continue;
			}

			$table_sql  = "-- Table structure for table {$escaped_table_name}\n";
			$table_sql .= "DROP TABLE IF EXISTS {$escaped_table_name};\n";
			$table_sql .= $create_table[1] . ";\n\n";

			$this->write_sql_chunk( $use_compression, $handle, $table_sql );

			$data_result = $this->export_table_data( $use_compression, $handle, $escaped_table_name );
			if ( is_wp_error( $data_result ) ) {
				return $data_result;
			}

			$this->write_sql_chunk( $use_compression, $handle, "\n" );
		}

		return true;
	}

	/**
	 * Export data for a single table.
	 *
	 * @since 1.0.2
	 * @param bool   $use_compression    Whether to use compression.
	 * @param mixed  $handle             File handle resource.
	 * @param string $escaped_table_name Escaped table name.
	 * @return true|WP_Error
	 */
	private function export_table_data( bool $use_compression, $handle, string $escaped_table_name ) {
		global $wpdb;

		$data_header = "-- Dumping data for table {$escaped_table_name}\n";
		$this->write_sql_chunk( $use_compression, $handle, $data_header );

		$offset       = 0;
		$limit        = 1000;
		$result_count = $limit;

		do {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name sanitized above.
					"SELECT * FROM {$escaped_table_name} LIMIT %d OFFSET %d",
					$limit,
					$offset
				),
				ARRAY_A
			);

			$result_count = count( $results );
			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					$columns         = array_keys( $row );
					$quoted_columns  = array();
					$placeholders    = array();
					$prepared_values = array();

					foreach ( $columns as $col_name ) {
						$quoted = $this->sanitize_identifier( (string) $col_name );
						if ( '' === $quoted ) {
							continue;
						}

						$quoted_columns[] = $quoted;
						$value            = $row[ $col_name ];

						if ( null === $value ) {
							$placeholders[] = 'NULL';
							continue;
						}

						$placeholders[]    = '%s';
						$prepared_values[] = $value;
					}

					if ( empty( $quoted_columns ) ) {
						continue;
					}

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Identifiers sanitized manually above.
					$query_template = 'INSERT INTO ' . $escaped_table_name . ' (' . implode( ', ', $quoted_columns ) . ') VALUES (' . implode( ', ', $placeholders ) . ')';

					if ( empty( $prepared_values ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query contains only literal NULL values.
						$this->write_sql_chunk( $use_compression, $handle, $query_template . "\n" );
						continue;
					}

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query template sanitized before preparing.
					$prepared_sql = $wpdb->prepare( $query_template, $prepared_values );
					if ( false === $prepared_sql ) {
						continue;
					}

					$this->write_sql_chunk( $use_compression, $handle, $prepared_sql . "\n" );
				}
			}

			$offset += $limit;
		} while ( $result_count === $limit );

		return true;
	}

	/**
	 * Close an SQL file handle safely.
	 *
	 * @since 2.0.3
	 * @param bool  $use_compression Whether compression was used.
	 * @param mixed $handle          File handle resource.
	 * @return void
	 */
	private function close_sql_handle( bool $use_compression, $handle ): void {
		if ( ! is_resource( $handle ) ) {
			return;
		}

		if ( $use_compression ) {
			gzclose( $handle );
			return;
		}

		fflush( $handle );
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming exports requires native handles.
		fclose( $handle );
	}

	/**
	 * Transfer backup to remote server.
	 *
	 * @since 1.0.2
	 * @param string $backup_file Path to backup file.
	 * @return true|WP_Error
	 */
	private function transfer_backup( string $backup_file ) {
		$ssh_host          = Options::get( 'ssh_host' );
		$ssh_port          = Options::get( 'ssh_port' );
		$ssh_user          = Options::get( 'ssh_user' );
		$ssh_password      = Options::get( 'ssh_password' );
		$remote_backup_dir = Options::get( 'remote_backup_dir' );

		$connection = \ssh2_connect( $ssh_host, (int) $ssh_port );
		if ( false === $connection ) {
			$message = 'SSH connection failed';
			Pushover::send( 'Backup transfer failed: SSH connection failed.', 1 );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return new WP_Error( 'ssh_connection_failed', $message );
		}

		if ( ! \ssh2_auth_password( $connection, $ssh_user, $ssh_password ) ) {
			$message = 'SSH authentication failed';
			Pushover::send( 'Backup transfer failed: SSH authentication failed.', 1 );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
			return new WP_Error( 'ssh_auth_failed', $message );
		}

		if ( ! \ssh2_scp_send( $connection, $backup_file, $remote_backup_dir . '/' . basename( $backup_file ) ) ) {
			Pushover::send( 'Backup transfer failed: SCP send failed.', 1 );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'SCP send failed' );
			}
			return new WP_Error( 'scp_send_failed', 'SCP send failed' );
		}

		return true;
	}

	/**
	 * Manage backups on remote server (delete old backups).
	 *
	 * @since 1.0.2
	 * @return void
	 */
	private function manage_remote_backups(): void {
		$ssh_host          = Options::get( 'ssh_host' );
		$ssh_port          = Options::get( 'ssh_port' );
		$ssh_user          = Options::get( 'ssh_user' );
		$ssh_password      = Options::get( 'ssh_password' );
		$remote_backup_dir = Options::get( 'remote_backup_dir' );

		$connection = \ssh2_connect( $ssh_host, (int) $ssh_port );
		if ( false === $connection ) {
			return;
		}

		if ( ! \ssh2_auth_password( $connection, $ssh_user, $ssh_password ) ) {
			return;
		}

		$sftp         = \ssh2_sftp( $connection );
		$remote_files = array();
		$dir          = 'ssh2.sftp://' . $sftp . $remote_backup_dir;
		$handle       = opendir( $dir );

		if ( false !== $handle ) {
			$entry = readdir( $handle );
			while ( false !== $entry ) {
				if ( '.' !== $entry && '..' !== $entry ) {
					$remote_files[] = $entry;
				}
				$entry = readdir( $handle );
			}
			closedir( $handle );
		}

		$max_backups = Options::get( 'max_backups', '5' );
		if ( empty( $max_backups ) ) {
			$max_backups = 5;
		}

		if ( count( $remote_files ) > (int) $max_backups ) {
			sort( $remote_files );
			$files_to_delete = array_slice( $remote_files, 0, count( $remote_files ) - (int) $max_backups );
			foreach ( $files_to_delete as $file ) {
				\ssh2_sftp_unlink( $sftp, $remote_backup_dir . '/' . $file );
			}
		}
	}
}
