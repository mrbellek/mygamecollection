# My Game Collection
Import your [TrueAchievements][1] game collection into this CMS to get a better insight into it. Requires some manual data entry for full functionality.

![MyGameCollection screenshot](https://i.imgur.com/V4DIDVy.png)

This is a small CMS written in PHP, that can import your game collection from TrueAchievements (csv) and display some info on it and apply some filters that the regular TA game collection doesn't allow (like ['show games that are not backwards compatible'][3]). If you go the distance and also how much you paid for each game, it can give some insight into how much you spent in total, how much you saved by comparing it to current prices. There's a lot of info TA has that it can't/won't show you that's very useful, and I put a lot of that information at your fingertips here.

A selection:
- which of your games are delisted
- total estimated playtime combined over all your games, and how much you already spent
- sort by highest-rated games you have not started yet
- recently purchased games
- longest or shortest games

Some of the data that this uses will require you to manually enter them, some of it on TA, some of it here. If you do this, even more filters are available, like:
- list of your physical games (TA)
- games you no longer own (TA)
- not backwards-compatible games
- not backwards-compatible games with online multiplayer achievements
- games you want to start soon (shortlist)

I made this little CMS for fun for myself, so expect a lot of the code to be garbage quality, cobbled-together and poorly commented. Contributions are welcome! Fork the code, [grab an issue from the list][2] and make a pull request when you're done. Cheers! :)

[1]: https://www.trueachievements.com/
[2]: https://github.com/mrbellek/mygamecollection/issues
[3]: https://www.trueachievements.com/forum/viewthread.aspx?tid=907135
