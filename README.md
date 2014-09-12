CEDECAI
=======

以下内容をまとめつつブログ書きました。

- [PHPを使ったり4000円課金したりしてCEDEC AI CHALLENGE 準優勝に至るまでの軌跡](http://dekokun.github.io/posts/2014-09-07.html)

## 戦略

- ルールベースAIとモンテカルロなAIの組み合わせ
- 最初は「ただのルールベースAIで行くぞ」と思ってルールベースの土台を作ったが、結局「1回目は全ブッコミルール、それ以降はモンテカルロ法ルール」という感じだったため、ルールベースAIを導入した意味はほとんどなかった

### 戦略概要

- 一手目は、情熱度が高いユーザをランダムに選択し全ポイントをぶっこむ
- 二手目以降はモンテカルロ法にもとづいて決める
  - 試行回数は、自分の取りうる全ての手に対して25回。少なすぎる。

### モンテカルロ法について

- プログラム内に全ての自分が打ち得る手を配列の形で列挙しておく
- 自分が取りうる手のどれを打った際にその後全員のプレイヤーがランダムに打った際の勝率が高いかを計算して自分の手とする

### なぜモンテカルロ？

意思決定の流れを時系列順に

- 「このAI,モンテカルロが向いているんちゃいますか？」という後輩の進言によってモンテカルロ法のAIについて調べる
- 調べたところ「どのような手を打っても確実にゲームは終了する」というゲームの特徴がモンテカルロ法にぴったりだった
- 唯一の懸念点は、PHPを選択していることで「Scala等のコンパイル型の言語を選んだ参加者と比べて計算量が圧倒的に少なく試行回数が稼げない」ところ
- 以下『工夫したところ』『反省点』にあるように、今回の問題はモンテカルロ法を選択した際に計算量を減らす工夫が色々できそうだったのでまぁ、他の参加者を出し抜くことも可能だろう
- モンテカルロに決定

### 工夫したところ

- 自分が取りうる手は状況によって変わらないためプログラム内に自分の打ち得る手を最初に列挙しておいたところ
- 過去のデート回数については、1ターン毎の各ヒロインのデート回数を、そのターンでの全デート回数で割った数を、自分以外の各プレイヤが取得した好感度として計算 <- この考えを入れることで明らかに勝率が上がった
- 今回、「敵がランダムに行動する」という仮定をとった際、自分がとった手は全く相手の手に影響を与えないため（例えば囲碁の場合、自分が打った場所には敵は打てないが、今回はそのような制約はない）、ランダムな、全プレイヤーが取りうる手の中で、自分がとる手を入れ替えていくことで計算量を減らすという戦略をとった
- このゲームでは、「敵がランダムに行動する」という仮定をとった際、「ある手を打つことが決まっているのであれば、それがどのタイミングだとしても最終結果は変わらない」ため、あるプレイヤーが今後取りうる手を計算する際に、「全45ポイントをどのヒロインに割り振るか」だけを計算することで、少ない試行回数で有意義な結果を得やすい環境を作った
    - これによって、一人のプレイヤーが取りうる手の組み合わせ結果が 8^45(ものすごくたくさん) から 45C7(45379620) まで減った


## 反省点

- 一手目の時点でモンテカルロベースに考えても良かった。というか、一度手元で試行回数を増やしたモンテカルロ法で考えさせてその手を観察し、実際の戦いでは決め打ちでその手を打たせればよかった
- 自分が取りうるてを列挙する部分、全て手打ちでやったために、多分結構不正確だった
  - 事前に列挙するにしてもプログラムで生成したほうがよかった
- 一位だった確率の高い手を選択させる際に、一位だった確率が高い手が複数存在した際に0番、1番のヒロインに偏りやすいプログラミングだったのがよくなかった。バラけさせるべき。試行回数が少なかったことから結構そのような状態が発生していたのではないかと考えられる
  - 実際、初期のターンで0番のヒロインに振込まくっている様子だった
- 自分が一位かを計算する部分を、毎回全ての状態から計算していたせいで、そこが一番時間かかっていた（プロファイリングをとったところ、全体の実行時間の実に78%が自分が一位かを計算する部分で占められていた）
  - 変化するのは自分の手だけであるため、そこだけ入れ替えて計算すればよかった
- 休日と平日で、自分が取りうるては全然違ってくるため、平日と休日の試行回数を変えればよかった

## 感想

- アイデアをくれた後輩に感謝
- モンテカルロ法、実行結果をみても正しく動いているかがよくわからないためデバッグが著しく困難
- 決勝に出られなかったの極めて悔しい
- PHPの「division by zero エラーでもプログラムが止まらない」という特性に救われた
- 試行回数が25回しかなかったのになぜ結構いい順位だったのかが極めて謎

## 決勝終わって

良かった点

- ライバルとの反省会
  - それをちゃんとフィードバック
- ライバルとの情報交換
- JenkinsによるCI
- Jenkinsに金をかけた
- プロファイラでボトルネックを測定した
- 評価関数を勝負にしたのも地味によかったんじゃないかとは思うんだけどわからん

## 特殊な事たち

- /proc/cpuinfoを覗き見て並列処理は無意味だと知る
  - 複数コアだったら外部プロセスを生やして頑張ろうとしていた
- 外部のAPIを叩けないか調べる
  - 運営によると「外部への通信は制限していなかったはず」と言っていたので何か間違えたかも
- 試行回数を増やした上でJenkinsに頑張らせて、ある手が出たらこの手を返すというのをひたすら計算してファイルに持っておく
- 複数アカウントを作成し、協力動作させたりしたら予選楽勝だなと思ったがあまりの邪悪さ(というか確実にダメな行為)に手を出さなかった
  - そもそも予選突破したいなら普通に努力したほうが勝てるし


