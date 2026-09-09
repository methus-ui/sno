<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Optimize Messaging Performance Migration
 *
 * Adds indexes and optimizations for messaging queries
 * to improve performance under high load.
 *
 * Phase 6: Performance Optimization
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // ===== CONVERSATIONS TABLE OPTIMIZATIONS =====

        Schema::table('conversations', function (Blueprint $table) {
            // Composite index for list queries (user type + unread filtering)
            if (!$this->indexExists('conversations', 'idx_conversations_user_unread')) {
                $table->index(['user_id', 'unread_message_count'], 'idx_conversations_user_unread');
            }

            // Index for archived conversations
            if (!$this->indexExists('conversations', 'idx_conversations_archived')) {
                $table->index(['is_archived', 'updated_at'], 'idx_conversations_archived');
            }

            // Composite index for vendor conversations
            if (!$this->indexExists('conversations', 'idx_conversations_store')) {
                $table->index(['store_id', 'updated_at'], 'idx_conversations_store');
            }

            // Index for last message timestamp (sorting)
            if (!$this->indexExists('conversations', 'idx_conversations_last_message')) {
                $table->index('last_message_time', 'idx_conversations_last_message');
            }
        });

        // ===== MESSAGES TABLE OPTIMIZATIONS =====

        Schema::table('messages', function (Blueprint $table) {
            // Composite index for conversation messages with soft deletes
            if (!$this->indexExists('messages', 'idx_messages_conversation_deleted')) {
                $table->index(['conversation_id', 'deleted_at', 'created_at'], 'idx_messages_conversation_deleted');
            }

            // Index for sender queries
            if (!$this->indexExists('messages', 'idx_messages_sender')) {
                $table->index(['sender_id', 'created_at'], 'idx_messages_sender');
            }

            // Index for unread messages
            if (!$this->indexExists('messages', 'idx_messages_unread')) {
                $table->index(['is_read', 'created_at'], 'idx_messages_unread');
            }

            // Index for order-related messages
            if (!$this->indexExists('messages', 'idx_messages_order')) {
                $table->index('order_id', 'idx_messages_order');
            }
        });

        // ===== MESSAGE TEMPLATES OPTIMIZATIONS =====

        Schema::table('message_templates', function (Blueprint $table) {
            // Composite index for active templates by user type
            if (!$this->indexExists('message_templates', 'idx_templates_user_active')) {
                $table->index(['user_type', 'is_active', 'sort_order'], 'idx_templates_user_active');
            }
        });

        // ===== MESSAGE DELIVERY STATUS OPTIMIZATIONS =====

        if (Schema::hasTable('message_delivery_status')) {
            Schema::table('message_delivery_status', function (Blueprint $table) {
                // Composite index for retry queries
                if (!$this->indexExists('message_delivery_status', 'idx_delivery_status_retry')) {
                    $table->index(['status', 'retry_count', 'created_at'], 'idx_delivery_status_retry');
                }
            });
        }

        // ===== MESSAGE REACTIONS OPTIMIZATIONS =====

        if (Schema::hasTable('message_reactions')) {
            Schema::table('message_reactions', function (Blueprint $table) {
                // Composite index for reaction counts
                if (!$this->indexExists('message_reactions', 'idx_reactions_message_type')) {
                    $table->index(['message_id', 'reaction_type'], 'idx_reactions_message_type');
                }
            });
        }

        // ===== QUERY OPTIMIZATION HINTS =====

        // Add query optimization hints for MySQL
        if (DB::getDriverName() === 'mysql') {
            // Optimize conversations table
            DB::statement('OPTIMIZE TABLE conversations');

            // Optimize messages table
            DB::statement('OPTIMIZE TABLE messages');

            // Analyze tables for better query planning
            DB::statement('ANALYZE TABLE conversations, messages, message_templates');
        }

        \Log::info('Performance optimization migration completed successfully');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_user_unread');
            $table->dropIndex('idx_conversations_archived');
            $table->dropIndex('idx_conversations_store');
            $table->dropIndex('idx_conversations_last_message');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conversation_deleted');
            $table->dropIndex('idx_messages_sender');
            $table->dropIndex('idx_messages_unread');
            $table->dropIndex('idx_messages_order');
        });

        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex('idx_templates_user_active');
        });

        if (Schema::hasTable('message_delivery_status')) {
            Schema::table('message_delivery_status', function (Blueprint $table) {
                $table->dropIndex('idx_delivery_status_retry');
            });
        }

        if (Schema::hasTable('message_reactions')) {
            Schema::table('message_reactions', function (Blueprint $table) {
                $table->dropIndex('idx_reactions_message_type');
            });
        }

        \Log::info('Performance optimization migration rolled back');
    }

    /**
     * Check if index exists
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    protected function indexExists($table, $index)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
