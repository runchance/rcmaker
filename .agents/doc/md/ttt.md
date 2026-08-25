# 空间查询 SQL 示例

以下内容是 PostGIS / PostgreSQL 空间查询示例，用于展示点位表、距离排序和 GeoJSON 输出。

## 表结构

```sql
CREATE TABLE species_distribution (
  id SERIAL PRIMARY KEY,
  species_name VARCHAR(50),
  geom GEOMETRY(Point, 4326)
);
```

## 距离排序

```sql
SELECT species_name, ST_Distance(geom, ST_SetSRID(ST_MakePoint(lon, lat), 4326)) AS distance
FROM species_distribution
ORDER BY distance ASC
LIMIT 1;
```

## GeoJSON 输出

```sql
SELECT species_name, ST_AsGeoJSON(geom) AS geojson
FROM species_distribution;
```
