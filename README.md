# `heroku-hc`

## Usage

```
docker run --rm \
  -e INTERVAL=600 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```

### Random Seconds Interval

To set random seconds in the interval you can use `INTERVAL` variable like this.

```
docker run --rm \
  -e INTERVAL=100-600 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```

### Checking At Specific Hours

To check at specific hours you can use `HOURS` variable like this.

```
docker run --rm \
  -e HOURS=9-12,14-19 \
  -e HC_URL=https://your-app.herokuapp.com/hc.html \
  -d sameyasu/heroku-hc
```
