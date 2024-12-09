<?php
class config
{
    private $_conn;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $this->_conn = new SQLite3('/Users/binblacker/sg-4241725359072075-main/database.sqlite');
        if (!$this->_conn) {
            die('Connection failed: ' . $this->_conn->lastErrorMsg());
        }
    }

    public function dis_connect()
    {
        if ($this->_conn) {
            $this->_conn->close();
        }
    }

    public function query($sql)
    {
        $result = $this->_conn->query($sql);
        if (!$result) {
            die('Query failed: ' . $this->_conn->lastErrorMsg());
        }
        return $result;
    }

    public function prepare($sql)
    {
        $stmt = $this->_conn->prepare($sql);
        if (!$stmt) {
            die('Prepare statement failed: ' . $this->_conn->lastErrorMsg());
        }
        return $stmt;
    }

    // Thêm phương thức lastInsertRowID
    public function lastInsertRowID()
    {
        return $this->_conn->lastInsertRowID();
    }

    // Thêm phương thức exec để thực thi các câu lệnh không trả về kết quả
    public function exec($sql)
    {
        $result = $this->_conn->exec($sql);
        if ($result === false) {
            die('Exec failed: ' . $this->_conn->lastErrorMsg());
        }
        return $result;
    }

    // Thêm phương thức escapeString để tránh SQL injection
    public function escapeString($string)
    {
        return $this->_conn->escapeString($string);
    }
}

// Usage