# `heroku-hc`

## Usage

```
docker run --rm \
  -e TZ=Asia/Tokyo \
  -e INTERVAL=600 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```

### Random Seconds Interval

To set random seconds in the interval you can use `INTERVAL` variable like this.

```
docker run --rm \
  -e TZ=Asia/Tokyo \
  -e INTERVAL=100-600 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```

### Checking At Specific Time

- Specfic period of hours

To check at specific hours you can use `HOURS` variable like this.

```
docker run --rm \
  -e TZ=Asia/Tokyo \
  -e HOURS=9-12,14-19 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```

- Specific days of week

To check at specific days of week you can use `WEEKDAYS` variable like this, using ISO-8601 format.

```
docker run --rm \
  -e TZ=Asia/Tokyo \
  -e WEEKDAYS=2-6 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```

|Day of week|Value|
|---|---|
|Sunday|1|
|Monday|2|
|Tuesday|3|
|Wednesday|4|
|Thursday|5|
|Friday|6|
|Saturday|7|
