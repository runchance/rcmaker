#以集群方式启动9000到9005的Redis服务
for port in {9000..9005}
do
#关闭已经启动的服务，删除临时文件
redis-cli -c -p $port -h 192.168.128.132 shutdown
rm -f $port/dump*
rm -f $port/nodes*
done;
#start redis
for port in {9000..9005}
do src/redis-server $port/redis-cluster.conf
done;
#create cluster
echo yes|src/redis-cli --cluster create  192.168.128.132:9000 192.168.128.132:9001 192.168.128.132:9002 192.168.128.132:9003 192.168.128.132:9004 192.168.128.132:9005  --cluster-replicas 1
