<?php
    // keeps sessions in the database instead of files.
    // only used on Vercel (api/index.php turns it on): there every request can run on a different
    // short-lived server, so session files would get lost. on XAMPP normal file sessions are used.
    // php needs a class with these 6 functions to save sessions somewhere else.

    class DatabaseSessions implements SessionHandlerInterface {

        public function open($path, $name): bool {
            return true;
        }

        public function close(): bool {
            return true;
        }

        // the saved session text, or "" for a new session
        public function read($id): string|false {
            global $conn;

            $result = pg_query_params($conn, 'SELECT data FROM sessions WHERE session_id = $1', [$id]);

            if(!$result || pg_num_rows($result) == 0){
                return "";
            }
            return pg_fetch_result($result, 0, 0);
        }

        // insert, or update if it's already there
        public function write($id, $data): bool {
            global $conn;

            $result = pg_query_params($conn,
                'INSERT INTO sessions (session_id, data, updated_at) VALUES ($1, $2, now())
                 ON CONFLICT (session_id) DO UPDATE SET data = $2, updated_at = now()',
                [$id, $data]
            );
            return $result !== false;
        }

        public function destroy($id): bool {
            global $conn;

            pg_query_params($conn, 'DELETE FROM sessions WHERE session_id = $1', [$id]);
            return true;
        }

        // php calls this now and then to remove old sessions
        public function gc($maxLifetime): int|false {
            global $conn;

            $result = pg_query_params($conn, 'DELETE FROM sessions WHERE updated_at < now() - make_interval(secs => $1)', [$maxLifetime]);

            if(!$result){
                return false;
            }
            return pg_affected_rows($result);
        }
    }
