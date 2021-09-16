const { ApolloServer, gql } = require('apollo-server');

const schema = gql(`
  input EventInput {
    id: ID!
    cid: ID!
    title: String!
    lecturer: String!
    type: EventType!
  }

  enum Visibility {
    open
    learner
    lecturer
  }

  enum EventType {
    scheduled
    upload
    livestream
  }

  type Course {
    id: ID!
    visibility: Visibility
    events: [Event]
  }

  type Event {
    id: ID!
    title: String!
    lecturer: String!
    type: EventType
  }

  type Query {
    events(id: ID!): [Event]
  }

  type Mutation {
    addEvent(input: EventInput): Event
    removeEvent(id: ID!): Event
  }
`);
// Sortierung: Sollte die in einer Event-Liste gespeichert werden?

const events = [
  {
    id: '123-a',
    title: 'Grundlagen zu Quantenstrudel',
    lecturer: 'Prof. Dr. Proton',
    type: 'upload'
  },
  {
    id: '123-b',
    title: 'Quantenstrudel: Wie sie dein Wasser reinigen',
    lecturer: 'Prof. Dr. Proton',
    type: 'upload'
  },
  {
    id: '123-c',
    title: 'Quantenstrudel: Technische Umsetzung',
    lecturer: 'Prof. Dr. Proton',
    type: 'upload'
  }
]

const courses = [
  {
    id: 'test',
    visibility: 'open',
    events: events
  }
]

var resolvers = {
  Query: {
    events: (parent, args) => {
      return courses.find(course => course.id === args.id).events
    }
  },
  Mutation: {
    addEvent: (parent, args) => {
      var event = {
        id: args.input.id,
        title: args.input.title,
        lecturer: args.input.lecturer,
        type: args.input.type
      }
      courses.find(course => course.id === args.input.cid).events.push(event)
      return event
    },
    removeEvent: (parent, args) => {
      var event = events.find(event => event.id === args.id)
      if (event) {
        events.splice(events.indexOf(event))
      }
      return event
    }
  }
};

const server = new ApolloServer({ 
  typeDefs: schema, 
  resolvers: resolvers
});

server.listen(4001).then(({ url }) => {
  console.log('API server running at localhost:4001');
});
