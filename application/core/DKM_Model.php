<?php

abstract class DKM_Model extends CI_Model {

    // 子类需要声明
    protected $table_name = NULL;

    public function execute($statement, $binds = NULL) {
        if (empty($binds)) {
            return $this->db->query($statement);
        } else {
            return $this->db->query($statement, $binds);
        }
    }

    public function query($query_array, $where_array) {
        return $this->db->select($query_array)->from($this->table_name)->where($where_array)->get();
    }

    public function update($field_array, $where_array) {
        return $this->db->update($this->table_name, $field_array, $where_array);
    }

    public function update_batch($field_array, $index) {
        return $this->db->update_batch($this->table_name, $field_array, $index);
    }

    public function insert_batch($field_array) {
        return $this->db->insert_batch($this->table_name, $field_array);
    }

    public function insert($data_array) {
        return $this->db->insert($this->table_name, $data_array);
    }

    public function insert_id() {
        return $this->db->insert_id();
    }

    public function affected_rows() {
        return $this->db->affected_rows();
    }

    public function get_by_id($id, $field = 'id') {
        if (!check_id($id)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, [$field => $id])->row();
    }

    public function get_by_ids($ids, $field = 'id') {
        if (!check_ids($ids)) {
            return FALSE;
        }
        return $this->db->from($this->table_name)->where_in($field, $ids)->get()->result();
    }

    public function update_by_id($id, $field_array, $gmt_update = TRUE) {
        if (empty($field_array) || !is_array($field_array)) {
            return FALSE;
        }
        if (!isset($field_array['gmt_update']) && $gmt_update == TRUE) {
            $field_array['gmt_update'] = time();
        }
        $this->update($field_array, ['id' => $id]);

        return $this->affected_rows();
    }

    final public function safe_count_by_where(MixedQueryCondition $qc) {
        $table_name = $this->table_name;
        if (!$table_name) {
            return FALSE;
        }
        if ($wheres = $qc->get_where()) {
            foreach ($wheres as $where) {
                list($field, $operator, $value) = $where;
                $this->db->where($field . ' ' . $operator, $value);
            }
        }
        if ($or_wheres = $qc->get_or_where()) {
            foreach ($or_wheres as $where) {
                list($field, $operator, $value) = $where;
                $this->db->or_where($field . ' ' . $operator, $value);
            }
        }
        if ($where_ins = $qc->get_wherein()) {
            foreach ($where_ins as $where_in) {
                list($field, $values) = $where_in;
                $this->db->where_in($field, $values);
            }
        }

        if ($where_no_ins = $qc->get_where_notin()) {
            foreach ($where_no_ins as $where_not_in) {
                list($field, $values) = $where_not_in;
                $this->db->where_not_in($field, $values);
            }
        }

        return $this->db->from($table_name)->count_all_results();
    }

    final public function safe_list_by_page_where(MixedQueryCondition $qc, $page = 0, $page_size = 0) {
        $table_name = $this->table_name;
        if (!$table_name) {
            return FALSE;
        }
        if ($wheres = $qc->get_where()) {
            foreach ($wheres as $where) {
                list($field, $operator, $value) = $where;
                $this->db->where($field . ' ' . $operator, $value);
            }
        }
        if ($or_wheres = $qc->get_or_where()) {
            foreach ($or_wheres as $where) {
                list($field, $operator, $value) = $where;
                $this->db->or_where($field . ' ' . $operator, $value);
            }
        }
        if ($where_ins = $qc->get_wherein()) {
            foreach ($where_ins as $where_in) {
                list($field, $values) = $where_in;
                $this->db->where_in($field, $values);
            }
        }

        if ($order_bys = $qc->get_order_by()) {
            foreach ($order_bys as $order_by) {
                list($field, $sort) = $order_by;
                $this->db->order_by($field, $sort);
            }
        }

        if ($where_no_ins = $qc->get_where_notin()) {
            foreach ($where_no_ins as $where_not_in) {
                list($field, $values) = $where_not_in;
                $this->db->where_not_in($field, $values);
            }
        }
        if ($page > 0 && $page_size > 0) {
            $this->db->limit($page_size, ($page - 1) * $page_size);
        }

        return $this->db->get($table_name)->result();
    }
}