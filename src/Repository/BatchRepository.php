<?php
namespace Kitmage\TrainingEntitlements\Repository;

final class BatchRepository {
	private function table() { global $wpdb; return $wpdb->prefix . 'kte_batches'; }
	public function create( array $data ) { global $wpdb; $ok=$wpdb->insert($this->table(),$data); return $ok ? (int)$wpdb->insert_id : 0; }
	public function get( $id ) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id=%d",$id)); }
	public function by_hash( $hash ) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE token_hash=%s",$hash)); }
	public function by_order_item( $id ) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE order_item_id=%d",$id)); }
	public function for_customer( $id ) { global $wpdb; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE customer_user_id=%d ORDER BY created_at DESC",$id)); }
	public function query( $args, &$total ) {
		global $wpdb; $where='1=1'; $values=array();
		if(!empty($args['status'])){$where.=' AND status=%s';$values[]=$args['status'];}
		if(!empty($args['s'])){$term='%'.$wpdb->esc_like($args['s']).'%';$where.=' AND (CAST(order_id AS CHAR) LIKE %s OR CAST(subscription_id AS CHAR) LIKE %s OR CAST(customer_user_id AS CHAR) LIKE %s)';array_push($values,$term,$term,$term);}
		$total=(int)$wpdb->get_var($values?$wpdb->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE $where",...$values):"SELECT COUNT(*) FROM {$this->table()} WHERE $where");
		$values[]=(int)$args['limit'];$values[]=(int)$args['offset']; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table()} WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d",...$values));
	}
	public function update( $id, array $data ) { global $wpdb; $data['updated_at']=current_time('mysql',true); return $wpdb->update($this->table(),$data,array('id'=>(int)$id)); }
	public function refund_order( $order_id ) { global $wpdb; return $wpdb->query($wpdb->prepare("UPDATE {$this->table()} SET status='refunded',updated_at=%s WHERE order_id=%d AND status NOT IN ('refunded','revoked')",current_time('mysql',true),$order_id)); }
}
