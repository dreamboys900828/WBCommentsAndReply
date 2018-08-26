# WBCommentsAndReply
##微博评论&回复
```
    微博评论接口
    comments/to_me
    获取当前登录用户所接收到的评论列表
    
    URL
    https://api.weibo.com/2/comments/to_me.json
    JSON-GET
    需要授权
    访问接口频繁 需要与微博合作洽谈
    
    
    请求参数
    
 	         必选	类型及范围	说明
access_token	 true	string	采用OAuth授权方式为必填参数，OAuth授权后获得。
since_id	 false	int64	若指定此参数，则返回ID比since_id大的评论（即比since_id时间晚的评论），默认为0。
max_id	         false	int64	若指定此参数，则返回ID小于或等于max_id的评论，默认为0。
count	         false	int	单页返回的记录条数，默认为50。
page	         false	int	返回结果的页码，默认为1。
filter_by_author false	int	作者筛选类型，0：全部、1：我关注的人、2：陌生人，默认为0。
filter_by_source false	int	来源筛选类型，0：全部、1：来自微博的评论、2：来自微群的评论，默认为0。

```
```
    微博回复接口
    comments/reply
    回复一条评论
    
    URL
    https://api.weibo.com/2/comments/reply.json
    JSON-POST
    需要授权
    访问接口频繁 需要与微博合作洽谈
    
    请求参数
    
 	        必选	类型及范围	说明
access_token	true	string	采用OAuth授权方式为必填参数，OAuth授权后获得。
cid	        true	int64	需要回复的评论ID。
id	        true	int64	需要评论的微博ID。
comment	        true	string	回复评论内容，必须做URLencode，内容不超过140个汉字。
without_mention	false	int	回复中是否自动加入“回复@用户名”，0：是、1：否，默认为0。
comment_ori	false	int	当评论转发微博时，是否评论给原微博，0：否、1：是，默认为0。
rip	        false	string	开发者上报的操作用户真实IP，形如：211.156.0.1。
```